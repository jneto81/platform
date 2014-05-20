<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Component\Translation\Translator;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

class Manager implements FunctionProviderInterface, VirtualFieldProviderInterface
{
    /** @var ConfigurationObject */
    protected $config;

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var Translator */
    protected $translator;

    /** @var EntityHierarchyProvider  */
    protected $entityHierarchyProvider;

    /** @var array  */
    protected $virtualFields;

    /**
     * Constructor
     *
     * @param array                   $config
     * @param ConfigurationResolver   $resolver
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param Translator              $translator
     * @param VirtualFieldProvider    $virtualFieldProvider
     */
    public function __construct(
        array $config,
        ConfigurationResolver $resolver,
        EntityHierarchyProvider $entityHierarchyProvider,
        Translator $translator,
        VirtualFieldProvider $virtualFieldProvider
    ) {
        $resolver->resolve($config);
        $this->config                  = ConfigurationObject::create($config);
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->translator              = $translator;
        $this->virtualFields           = $virtualFieldProvider->getVirtualFields();
    }

    /**
     * Returns metadata for the given query type
     *
     * @param string $queryType The query type
     * @return array
     */
    public function getMetadata($queryType)
    {
        $filtersMetadata = [];
        $filters         = $this->getFilters($queryType);
        foreach ($filters as $filter) {
            $filtersMetadata[] = $filter->getMetadata();
        }

        return [
            'filters'    => $filtersMetadata,
            'grouping'   => $this->getMetadataForGrouping(),
            'converters' => $this->getMetadataForFunctions('converters', $queryType),
            'aggregates' => $this->getMetadataForFunctions('aggregates', $queryType),
            'hierarchy'  => $this->entityHierarchyProvider->getHierarchy()
        ];
    }

    /**
     * Add filter to array of available filters
     *
     * @param string          $type
     * @param FilterInterface $filter
     */
    public function addFilter($type, FilterInterface $filter)
    {
        $this->filters[$type] = $filter;
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name A filter name
     * @param array  $params An additional parameters of a new filter
     * @throws \RuntimeException if a filter with the given name does not exist
     * @return FilterInterface
     */
    public function createFilter($name, array $params = null)
    {
        $filtersConfig = $this->config->offsetGet('filters');
        if (!isset($filtersConfig[$name])) {
            throw new \RuntimeException(sprintf('Unknown filter "%s".', $name));
        }

        $config = $filtersConfig[$name];
        if ($params !== null && !empty($params)) {
            $config = array_merge($config, $params);
        }

        return $this->getFilterObject($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunction($name, $groupName, $groupType)
    {
        $result    = null;
        $functions = $this->config->offsetGetByPath(sprintf('[%s][%s][functions]', $groupType, $groupName));
        if ($functions !== null) {
            foreach ($functions as $function) {
                if ($function['name'] === $name) {
                    $result = $function;
                    break;
                }
            }
        }
        if ($result === null) {
            throw new InvalidConfigurationException(
                sprintf('The function "%s:%s:%s" was not found.', $groupType, $groupName, $name)
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        return isset($this->virtualFields[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        return $this->virtualFields[$className][$fieldName]['query'];
    }

    /**
     * Returns filters types
     *
     * @param array $filterNames
     * @return array
     */
    public function getExcludedProperties(array $filterNames)
    {
        $types   = [];
        $filters = $this->config->offsetGet('filters');
        foreach ($filterNames as $filterName) {
            unset($filters[$filterName]);
        }

        foreach ($filters as $filter) {
            if (isset($filter['applicable'])) {
                foreach ($filter['applicable'] as $type) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    /**
     * Returns all available filters for the given query type
     *
     * @param string $queryType The query type
     * @return FilterInterface[]
     */
    protected function getFilters($queryType)
    {
        $filters       = [];
        $filtersConfig = $this->config->offsetGet('filters');
        foreach ($filtersConfig as $name => $attr) {
            if ($this->isItemAllowedForQueryType($attr, $queryType)) {
                unset($attr['query_type']);
                $filters[$name] = $this->getFilterObject($name, $attr);
            }
        }

        return $filters;
    }

    /**
     * Returns prepared filter object
     *
     * @param string $name
     * @param array  $config
     *
     * @return FilterInterface
     */
    protected function getFilterObject($name, array $config)
    {
        $filter = clone $this->filters[$config['type']];
        $filter->init($name, $config);

        return $filter;
    }

    /**
     * Returns grouping metadata
     *
     * @return array
     */
    protected function getMetadataForGrouping()
    {
        return $this->config->offsetGet('grouping');
    }

    /**
     * Returns all available functions for the given query type
     *
     * @param string $groupType The type of functions' group
     * @param string $queryType The query type
     * @return array
     */
    protected function getMetadataForFunctions($groupType, $queryType)
    {
        $result       = [];
        $groupsConfig = $this->config->offsetGet($groupType);
        foreach ($groupsConfig as $name => $attr) {
            if ($this->isItemAllowedForQueryType($attr, $queryType)) {
                unset($attr['query_type']);
                $functions = [];
                foreach ($attr['functions'] as $function) {
                    $nameText    = empty($function['name_label'])
                        ? null // if a label is empty it means that this function should inherit a label
                        : $this->translator->trans($function['name_label']);
                    $hintText    = empty($function['hint_label'])
                        ? null // if a label is empty it means that this function should inherit a label
                        : $this->translator->trans($function['hint_label']);
                    $functions[] = [
                        'name'  => $function['name'],
                        'label' => $nameText,
                        'title' => $hintText,
                    ];
                }
                $attr['functions'] = $functions;
                $result[$name]     = $attr;
            }
        }

        return $result;
    }

    /**
     * Checks if an item can be used for the given query type
     *
     * @param array  $item An item to check
     * @param string $queryType The query type
     * @return bool true if the item can be used for the given query type; otherwise, false.
     */
    protected function isItemAllowedForQueryType(&$item, $queryType)
    {
        foreach ($item['query_type'] as $itemQueryType) {
            if ($itemQueryType === 'all' || $itemQueryType === $queryType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param string        $queryType
     *
     * @return bool
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName, $queryType = '')
    {
        $excludeRules = $this->getExcludeRules();
        $className    = $metadata->getReflectionClass()->getName();

        foreach ($excludeRules as $rule) {
            $entity        = $rule['entity'];
            $field         = $rule['field'];
            $type          = $rule['type'];
            $ruleQueryType = $rule['query_type'];

            $fieldType = $metadata->getTypeOfField($fieldName);

            // exclude entity
            $isExcludeEntity = !$field && $className === $entity && $queryType === $ruleQueryType;

            // exclude entity's field
            $isExcludeEntityField = $className === $entity && $field === $fieldName && $queryType === $ruleQueryType;

            // exclude by type
            $isExcludeByType = $fieldType === $type;

            // exclude by query type
            $isExcludeByQueryType = $ruleQueryType === $queryType;

            if ($isExcludeEntity || $isExcludeEntityField || $isExcludeByType || $isExcludeByQueryType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $associationName
     * @param string        $queryType
     *
     * @return bool
     */
    public function isIgnoredAssosiation(ClassMetadata $metadata, $associationName, $queryType = '')
    {
        $excludeRules = $this->getExcludeRules();
        $className    = $metadata->getReflectionClass()->getName();

        foreach ($excludeRules as $rule) {
            $entity        = $rule['entity'];
            $field         = $rule['field'];
            $ruleQueryType = $rule['query_type'];

            if ($entity === $className && $field === $associationName) {
                return true;
            }

            if ($ruleQueryType === $queryType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getExcludeRules()
    {
        $result = $this->config->offsetGet('exclude');

        // ensure keys exists
        $keys = ['entity', 'field', 'query_type', 'type'];
        foreach ($keys as $key) {
            foreach ($result as $i => $item) {
                if (!isset($item[$key])) {
                    $result[$i][$key] = false;
                }
            }
        }

        // set default false
        array_walk_recursive(
            $result,
            function(&$value) {
                $value = empty($value) ? false : $value;
            }
        );

        return $result;
    }
}
