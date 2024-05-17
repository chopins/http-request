<?php

use UI\UI;

return  [
    'title' => 'API测试',
    'width' => 800,
    'height' => 600,
    'fullscreen' => 0,
    'body' => [
        [
            'widget' => 'grid',
            'dir' => 'h',
            'padded' => 1,
            'child_left' => 1,
            'child_top' => 1,
            'child_width' => 100,
            'child_height' => 100,
            'child_hexpand' => 1,
            'child_halign' => 1,
            'child_vexpand' => 1,
            'child_valign' => 1,
            'childs' => [
                [
                    'widget' => 'box',
                    'dir' => 'v',
                    'padded' => 1,
                    'child_fit' => 1,
                    'child_left' => 1,
                    'child_top' => 1,
                    'child_width' => 10,
                    'child_height' => 100,
                    'child_hexpand' => 1,
                    'child_halign' => UI::ALIGN_FILL,
                    'child_vexpand' => 1,
                    'child_valign' => UI::ALIGN_FILL,
                    'childs' => [
                        // [
                        //     'widget' => 'box',
                        //     'dir' => 'h',
                        //     'child_fit' => 0,
                        //     'childs' => [
                        //         ['widget' => 'button', 'title' => '保存'],
                        //     ]
                        // ],

                        [
                            'widget' => 'table',
                            'bgColor' => '#EEEEEE',
                            'id' => 'requestList',
                            'change' => [
                                [HTTP::class, 'onChangeRequestName'],
                                [HTTP::class, 'onSelectRequest'],
                            ],
                            'th' => [
                                [
                                    'title' => '已有API', 'idx' => 0, 'type' => 'text', 'editable' => 1,
                                ],
                                [
                                    'title' => '操作', 'idx' => 1, 'type' => 'checkbox', 'editable' => 1,
                                ]
                            ],
                            'tbody' => []
                        ],
                    ]
                ],
                [
                    'widget' => 'grid',
                    'padded' => 0,
                    'child_left' => 26,
                    'child_top' => 1,
                    'child_width' => 55,
                    'child_height' => 100,
                    'child_hexpand' => 1,
                    'child_halign' => UI::ALIGN_FILL,
                    'child_vexpand' => 0,
                    'child_valign' => UI::ALIGN_FILL,
                    'childs' => [
                        [
                            'widget' => 'form',
                            'child_left' => 26,
                            'child_top' => 1,
                            'child_width' => 55,
                            'child_height' => 100,
                            'child_hexpand' => 1,
                            'child_halign' => UI::ALIGN_FILL,
                            'child_vexpand' => 0,
                            'child_valign' => UI::ALIGN_START,
                            'padded' => 1,
                            'id' => 'requestForm',
                            'childs' => [
                                [
                                    'widget' => 'grid', 'padded' => 1,
                                    'childs' => [
                                        [
                                            'widget' => 'input', 'type' => 'select', 'id' => 'method', 'option' => HTTP::HTTP_METHOD,
                                            'child_left' => 0,
                                            'child_top' => 1,
                                            'child_width' => 1,
                                            'child_height' => 10,
                                            'child_hexpand' => 1,
                                            'child_halign' => UI::ALIGN_FILL,
                                            'child_vexpand' => 1,
                                            'child_valign' => UI::ALIGN_CENTER,
                                        ],
                                        'URL' => [
                                            'widget' => 'input', 'type' => 'text', 'id' => 'url',
                                            'child_left' => 5,
                                            'child_top' => 1,
                                            'child_width' => 55,
                                            'child_height' => 10,
                                            'child_hexpand' => 1,
                                            'child_halign' => UI::ALIGN_FILL,
                                            'child_vexpand' => 1,
                                            'child_valign' => UI::ALIGN_CENTER,
                                        ],
                                    ]
                                ],
                                'Header' => ['widget' => 'input', 'type' => 'textarea', 'id' => 'header'],
                                'Body' => ['widget' => 'input', 'type' => 'textarea', 'id' => 'body'],
                                ['widget' => 'box', 'dir' => 'h', 'padded' => 1, 'child_fit' => 0, 'childs' => [
                                    [
                                        'widget' => 'button', 'type' => 'text', 'title' => '请求', 'stretchy' => 0,
                                        'click' => HTTP::$ui->event('HTTP::onRequest')
                                    ],
                                    [
                                        'widget' => 'button', 'type' => 'text', 'title' => '保存', 'stretchy' => 0,
                                        'click' => HTTP::$ui->event('HTTP::onSave')
                                    ],
                                    [
                                        'widget' => 'button', 'type' => 'text', 'title' => '另存为', 'stretchy' => 0,
                                        'click' => HTTP::$ui->event('HTTP::onSaveAs')
                                    ],
                                    [
                                        'widget' => 'input', 'type' => 'search', 'title' => '', 'stretchy' => 0,
                                        'change' => HTTP::$ui->event('HTTP::onSearch')
                                    ],
                                ]],

                            ],

                        ],
                        [
                            'widget' => 'group', 'title' => '输出', 'margin' => 1,
                            'child' => [
                                'widget' => 'box', 
                                'padded' => 1,
                                'dir' => 'h',
                                'childs' => [
                                    [
                                        'widget' => 'input', 'type' => 'textarea', 'wrap' => 1, 'id' => 'outputText',
                                    ]
                                ]
                            ],
                            'child_left' => 26,
                            'child_top' => 100,
                            'child_width' => 55,
                            'child_height' => 500,
                            'child_hexpand' => 1,
                            'child_halign' => UI::ALIGN_FILL,
                            'child_vexpand' => 1,
                            'child_valign' => UI::ALIGN_FILL,
                        ]
                    ]
                ],

            ]
        ],
    ]
];
