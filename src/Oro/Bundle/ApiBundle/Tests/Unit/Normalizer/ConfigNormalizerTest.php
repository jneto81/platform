<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig($config, $expectedConfig)
    {
        $normalizer = new ConfigNormalizer();

        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configLoaderFactory = new ConfigLoaderFactory($configExtensionRegistry);
        $configLoader = $configLoaderFactory->getLoader(ConfigUtil::DEFINITION);

        /** @var EntityDefinitionConfig $normalizedConfig */
        $normalizedConfig = $configLoader->load($config);
        $normalizer->normalizeConfig($normalizedConfig);

        self::assertEquals($expectedConfig, $normalizedConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeConfigProvider()
    {
        return [
            'ignored fields'                                             => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                        ],
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field111' => [
                                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                                        ],
                                        'field112' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_renamed_fields'  => ['realField2' => 'field2'],
                    'fields'           => [
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field112' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'replaced fields'                                            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field'        => [
                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                        ],
                        '_field'       => [
                            'property_path' => 'field',
                            'exclude'       => true
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field111'  => [
                                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                                        ],
                                        '_field111' => [
                                            'property_path' => 'field111',
                                            'exclude'       => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_renamed_fields'  => ['field' => '_field'],
                    '_excluded_fields' => ['_field'],
                    'fields'           => [
                        '_field'       => [
                            'property_path' => 'field',
                            'exclude'       => true
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    '_renamed_fields'  => ['field111' => '_field111'],
                                    '_excluded_fields' => ['_field111'],
                                    'fields'           => [
                                        '_field111' => [
                                            'property_path' => 'field111',
                                            'exclude'       => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on another field'                             => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'field depends on excluded field'                            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1'],
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'field depends on replaced field'                            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'  => [
                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                        ],
                        '_field1' => [
                            'property_path' => 'field1',
                            'exclude'       => true
                        ],
                        'field2'  => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_renamed_fields'  => ['field1' => '_field1'],
                    '_excluded_fields' => ['_field1'],
                    'fields'           => [
                        '_field1' => [
                            'property_path' => 'field1'
                        ],
                        'field2'  => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'excluded field depends on another excluded field'           => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1', 'field2'],
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'field depends on excluded computed field'                   => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ],
                        'field3' => [
                            'depends_on' => ['field2']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1', 'field2'],
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ],
                        'field3' => [
                            'depends_on' => ['field2']
                        ]
                    ]
                ]
            ],
            'nested field depends on another field'                      => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field' => [
                            'fields' => [
                                'field1' => [
                                    'exclude' => true
                                ],
                                'field2' => [
                                    'depends_on' => ['field1']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field' => [
                            '_excluded_fields' => ['field1'],
                            'fields'           => [
                                'field1' => null,
                                'field2' => [
                                    'depends_on' => ['field1']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on association child field'                   => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on association undefined child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field12' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field12' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on undefined association child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2' => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2' => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on association excluded child field'          => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => [
                                    'exclude' => true
                                ]
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            '_excluded_fields' => ['field11'],
                            'fields'           => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on excluded association child field'          => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'exclude' => true,
                            'fields'  => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['association1'],
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on excluded association and its child fields' => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2'       => [
                            'depends_on' => ['association1.association11.field111']
                        ],
                        'association1' => [
                            'exclude' => true,
                            'fields'  => [
                                'association11' => [
                                    'exclude' => true,
                                    'fields'  => [
                                        'field111' => [
                                            'exclude' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['association1'],
                    'fields'           => [
                        'field2'       => [
                            'depends_on' => ['association1.association11.field111']
                        ],
                        'association1' => [
                            '_excluded_fields' => ['association11'],
                            'fields'           => [
                                'association11' => [
                                    '_excluded_fields' => ['field111'],
                                    'fields'           => [
                                        'field111' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association'                                      => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id' => null
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association with excluded fields'                 => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association with composite identifier'            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id1' => null,
                                'id2' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id1' => null,
                                'id2' => null
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on collapsed association child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'depends_on' => ['association1.field11']
                        ],
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'depends_on' => ['association1.field11']
                        ],
                        'association1' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id' => null
                            ]
                        ]
                    ]
                ]
            ],
            'extended association'                                       => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'data_type' => 'association:manyToOne'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all'
                ]
            ],
            'computed association without query'                         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'target_class'  => 'Test\TargetClass',
                            'target_type'   => 'to-many',
                            'property_path' => '_'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all'
                ]
            ],
            'computed association with query'                            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'target_class'      => 'Test\TargetClass',
                            'target_type'       => 'to-many',
                            'property_path'     => '_',
                            'association_query' => $this->createMock(QueryBuilder::class)
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'target_class'      => 'Test\TargetClass',
                            'target_type'       => 'to-many',
                            'property_path'     => '_',
                            'association_query' => $this->createMock(QueryBuilder::class)
                        ]
                    ]
                ]
            ]
        ];
    }
}
