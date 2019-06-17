<?php
function va_tokenize_test() {
	$tests = [
		[
			'AIS',
			'\\\\: cascina de l{ }alpe e del{ }uffa <test>; blubb, a(-b1e/ra2buc$/ <inf.> <m.>',
			[
				'class' => 'B',
				'concepts' => [
					1
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '\\\\:',
						'IPA' => '',
						'Original' => ':',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [
							779
						],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'cascina',
						'IPA' => 'cascina',
						'Original' => 'cascina',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'de',
						'IPA' => 'de',
						'Original' => 'de',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'l',
						'IPA' => 'l',
						'Original' => 'l',
						'Trennzeichen' => '{ }',
						'Trennzeichen_Original' => '‿',
						'Trennzeichen_IPA' => '‿',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 4,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'alpe',
						'IPA' => 'alpe',
						'Original' => 'alpe',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 5,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'e',
						'IPA' => 'e',
						'Original' => 'e',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 6,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'del',
						'IPA' => 'del',
						'Original' => 'del',
						'Trennzeichen' => '{ }',
						'Trennzeichen_Original' => '‿',
						'Trennzeichen_IPA' => '‿',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 7,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'uffa',
						'IPA' => 'uffa',
						'Original' => 'uffa',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 8,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'blubb',
						'IPA' => 'blubb',
						'Original' => 'blubb',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [
							1
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'a(-b1e/ra2buc$/',
						'IPA' => 'ɑːβˈerɑbuʧ͉',
						'Original' => 'ā̜βérɒbuć̩',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 2,
						'Ebene_3' => 1,
						'Bemerkung' => 'inf. m.',
						'Genus' => 'm',
						'Konzepte' => [
							1
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						0 => [
							'Genus' => '',
							'Bemerkung' => 'test',
							'Konzepte' => [
								1
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						]
					],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'test bla <m f>',
			[
				'class' => 'B',
				'concepts' => [
					77
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'test',
						'IPA' => 'test',
						'Original' => 'test',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'bla',
						'IPA' => 'bla',
						'Original' => 'bla',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'test',
						'IPA' => 'test',
						'Original' => 'test',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW1',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'bla',
						'IPA' => 'bla',
						'Original' => 'bla',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW1',
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => 'm',
							'Bemerkung' => 'm',
							'Konzepte' => [
								77
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						],
						[
							'Genus' => 'f',
							'Bemerkung' => 'f',
							'Konzepte' => [
								77
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						]
					],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'BSA',
			'NASE\\\\, HUT',
			[
				'class' => 'B',
				'concepts' => [
					77
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => 'NASE,',
						'IPA' => 'nɐsə',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_Original' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'HUT',
						'IPA' => 'hutʰ',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_Original' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => '',
							'Konzepte' => [
								77
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						]
					],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		
		],
		
		[
			'ALD-I',
			'1c1a1s1 1d1e1l1<1b1>1 1c1a1s',
			[
				'class' => 'B',
				'concepts' => [
					99,
					77
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '1c1a1s',
						'IPA' => 'ʦas',
						'Original' => '',
						'Trennzeichen' => '1 ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '1d1e1l1<1b1>',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => '1 ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '1c1a1s',
						'IPA' => 'ʦas',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => '',
							'Konzepte' => [
								99,
								77
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						]
					],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => [
						'Missing character in ALD-II-IPA codepage: 1<1b1>'
					]
				]
			]
		
		],
		
		[
			'ALD-II',
			'1c1a1s1 1d1e1l1<1b1>1 1c1a1s',
			[
				'class' => 'B',
				'concepts' => [
					99,
					77
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '1c1a1s',
						'IPA' => 'ʦas',
						'Original' => '',
						'Trennzeichen' => '1 ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '1d1e1l1<1b1>',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => '1 ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '1c1a1s',
						'IPA' => 'ʦas',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => '',
							'Konzepte' => [
								99,
								77
							],
							'MTyp' => NULL,
							'PTyp' => NULL
						]
					],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => [
						'Missing character in ALD-II-IPA codepage: 1<1b1>'
					]
				]
			]
		],
		
		[
			'CROWD',
			'Suppe (ganz dolle Sache, schmeckt super), Haustür (oder so), étrôngé',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'Suppe',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'ganz dolle Sache, schmeckt super',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'Haustür',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 2,
						'Ebene_3' => 1,
						'Bemerkung' => 'oder so',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'étrôngé',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 3,
						'Ebene_3' => 1,
						'Genus' => '',
						'Bemerkung' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			' cal <m. f. > ; caso <n.> ; ca <m.f.n.> <toll>; cas<inf.>  <n.f.> <veraltet>',
			[
				'class' => 'B',
				'concepts' => [
					4
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'cal',
						'IPA' => 'cal',
						'Original' => 'cal',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'm.',
						'Genus' => 'm',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'cal',
						'IPA' => 'cal',
						'Original' => 'cal',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'f.',
						'Genus' => 'f',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'caso',
						'IPA' => 'caso',
						'Original' => 'caso',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'n.',
						'Genus' => 'n',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'ca',
						'IPA' => 'ca',
						'Original' => 'ca',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'm. toll',
						'Genus' => 'm',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'ca',
						'IPA' => 'ca',
						'Original' => 'ca',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'f. toll',
						'Genus' => 'f',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'ca',
						'IPA' => 'ca',
						'Original' => 'ca',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'n. toll',
						'Genus' => 'n',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'cas',
						'IPA' => 'cas',
						'Original' => 'cas',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 4,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'inf. n. veraltet',
						'Genus' => 'n',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'cas',
						'IPA' => 'cas',
						'Original' => 'cas',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 4,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'inf. f. veraltet',
						'Genus' => 'f',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'VALTS',
			'Der Tennen; die Haust\\\\(h\\\\)u:r',
			[
				'class' => 'M',
				'concepts' => [
					11
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'MTyp' => 'NEW0',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => 'm',
						'Bemerkung' => 'VALTS-Typ "Der"',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Genus' => 'm',
						'MTyp' => 1076,
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => 'm',
						'Bemerkung' => 'VALTS-Typ "Tennen"',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'MTyp' => 'NEW1',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Genus' => 'f',
						'Bemerkung' => 'VALTS-Typ "die"',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Genus' => 'f',
						'MTyp' => 'NEW2',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Genus' => 'f',
						'Bemerkung' => 'VALTS-Typ "Haust(h)ür"',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [
						[
							'Beta' => 'Der',
							'Orth' => 'Der',
							'Genus' => 'm',
							'Quelle' => 'VALTS'
						],
						[
							'Beta' => 'die',
							'Orth' => 'die',
							'Genus' => 'f',
							'Quelle' => 'VALTS'
						],
						[
							'Beta' => 'Haust\\\\(h\\\\)u:r',
							'Orth' => 'Haust(h)ür',
							'Genus' => 'f',
							'Quelle' => 'VALTS'
						]
					],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'ALJA',
			'to$\ma{o}',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'to$\ma{o}',
						'IPA' => 'tˈɔmaŏ',
						'Original' => "tò̩maͦ",
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'tome{a1}',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'tome{a1}',
						'IPA' => 'tomeɑ̆',
						'Original' => "<span style='position : relative'>tom<span style='position: relative'>e<span style='position: absolute; font-size: 60%; top: -0.7em; left: calc(50% - 0.3em);'>α</span></span></span>",
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'la cascina, il domani',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'la',
						'IPA' => 'la',
						'Original' => 'la',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'cascina',
						'IPA' => 'cascina',
						'Original' => 'cascina',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'il',
						'IPA' => 'il',
						'Original' => 'il',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 2,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => 'm',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'domani',
						'IPA' => 'domani',
						'Original' => 'domani',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 2,
						'Ebene_3' => 2,
						'Bemerkung' => '',
						'Genus' => 'm',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'TSA',
			'au:f der Oim',
			[
				'class' => 'M',
				'concepts' => [
					44
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'TSA-Typ "aüf"',
						'Genus' => '',
						'Konzepte' => [],
						'MTyp' => 'NEW0',
						'PTyp' => NULL,
						'Id_Tokengruppe' => 'NEW0'
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => 'TSA-Typ "der"',
						'Genus' => 'm',
						'Konzepte' => [
							699
						],
						'MTyp' => 'NEW1',
						'PTyp' => NULL,
						'Id_Tokengruppe' => 'NEW0'
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Bemerkung' => 'TSA-Typ "Oim"',
						'Genus' => '',
						'Konzepte' => [],
						'MTyp' => 'NEW2',
						'PTyp' => NULL,
						'Id_Tokengruppe' => 'NEW0'
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => 'TSA-Typ "aüf der Oim"',
							'Konzepte' => [
								44
							],
							'MTyp' => 'NEW3',
							'PTyp' => NULL
						]
					],
					'mtypes' => [
						[
							'Beta' => 'au:f',
							'Orth' => 'aüf',
							'Genus' => '',
							'Quelle' => 'TSA'
						],
						[
							'Beta' => 'der',
							'Orth' => 'der',
							'Genus' => 'm',
							'Quelle' => 'TSA'
						],
						[
							'Beta' => 'Oim',
							'Orth' => 'Oim',
							'Genus' => '',
							'Quelle' => 'TSA'
						],
						[
							'Beta' => 'au:f der Oim',
							'Orth' => 'aüf der Oim',
							'Genus' => '',
							'Quelle' => 'TSA'
						]
					],
					'ptypes' => [],
					'warnings' => []
				]
			]
		
		],
		
		[
			'SDS',
			'chno:dle < verbale Bezeichnung> ; tu:u:mlige <adverbiale Bezeichnung>; gu&($!%)/rkensaft; z tu:u:mlige <adverbiale Bezeichnung>; pfe(rc1',
			[
				'class' => 'P',
				'concepts' => [
					44
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'verbale Bezeichnung SDS-Typ "chnödle"',
						'Genus' => '',
						'Konzepte' => [
							44
						],
						'PTyp' => 1089,
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'adverbiale Bezeichnung SDS-Typ "tüümlige"',
						'Genus' => '',
						'Konzepte' => [
							44
						],
						'PTyp' => 1087,
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "gu&($!%)/rkensaft"',
						'Genus' => '',
						'Konzepte' => [
							44
						],
						'PTyp' => 'NEW0',
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 4,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "z"',
						'Genus' => '',
						'Konzepte' => [],
						'PTyp' => 1085,
						'MTyp' => NULL,
						'Id_Tokengruppe' => 'NEW0'
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 4,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => 'SDS-Typ "tüümlige"',
						'Genus' => '',
						'Konzepte' => [],
						'PTyp' => 1087,
						'MTyp' => NULL,
						'Id_Tokengruppe' => 'NEW0'
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 5,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "pfe̜rχ"',
						'Genus' => '',
						'Konzepte' => [
							44
						],
						'PTyp' => 'NEW1',
						'MTyp' => NULL,
						'Id_Tokengruppe' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => 'adverbiale Bezeichnung SDS-Typ "z tüümlige"',
							'PTyp' => 1153,
							'Konzepte' => [
								44
							],
							'MTyp' => NULL
						]
					],
					'ptypes' => [
						[
							'Beta' => 'gu&($!%)/rkensaft',
							'Original' => '',
							'Quelle' => 'SDS'
						],
						[
							'Beta' => 'pfe(rc1',
							'Original' => 'pfe̜rχ',
							'Quelle' => 'SDS'
						]
					],
					'mtypes' => [],
					'warnings' => [
						'Missing character in original codepage: u&($!%)/'
					]
				]
			]
		
		],
		
		[
			'ALP',
			'\\\\*\\\\? o1  talsa; a&$%/ba&$%/b&$%/',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '\\\\*\\\\?',
						'IPA' => '',
						'Original' => '*?',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							779
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'o1',
						'IPA' => 'ø',
						'Original' => 'œ',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'talsa',
						'IPA' => 'talsˈa',
						'Original' => 'talsa',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'a&$%/ba&$%/b&$%/',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							5
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => [
						'Missing character in ALP-IPA codepage: a&%/', // Dollar is accent => removed
						'Missing character in ALP-IPA codepage: b&$%/',
						'Missing character in original codepage: a&$%/',
						'Missing character in original codepage: b&$%/'
					]
				]
			]
		],
		
		[
			'BSA',
			'RA-M',
			[
				'class' => 'B',
				'concepts' => [
					11
				],
				'notes' => 'M',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => 'RA-M',
						'IPA' => 'ram',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'M',
						'Genus' => 'm',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'SDS',
			'U?:berlauf; U:berlauf',
			[
				'class' => 'M',
				'concepts' => [
					11
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "Ụ̈berlauf"',
						'Genus' => '',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => 'NEW0',
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "Überlauf"',
						'Genus' => '',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => 'NEW1',
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [
						[
							'Beta' => 'U?:berlauf',
							'Orth' => 'Ụ̈berlauf',
							'Genus' => '',
							'Quelle' => 'SDS'
						],
						[
							'Beta' => 'U:berlauf',
							'Orth' => 'Überlauf',
							'Genus' => '',
							'Quelle' => 'SDS'
						]
					],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'SDS',
			'Na\\\\(a\\\\)se',
			[
				'class' => 'M',
				'concepts' => [
					11
				],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'SDS-Typ "Na(a)se"',
						'Genus' => '',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => 'NEW0',
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [
						[
							'Beta' => 'Na\\\\(a\\\\)se',
							'Orth' => 'Na(a)se',
							'Genus' => '',
							'Quelle' => 'SDS'
						]
					],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'ta:st <wo anders sagt man #1ta:s1t##>',
			[
				'class' => 'B',
				'concepts' => [
					11
				],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => 'ta:st',
						'IPA' => 'tæst',
						'Original' => 'täst',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'wo anders sagt man täʃt',
						'Genus' => '',
						'Konzepte' => [
							11
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'\\\\:la\\ lo?do?le(/ta1',
			[
				'class' => 'B',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '\\\\:',
						'IPA' => '',
						'Original' => ':',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							779
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'la\\',
						'IPA' => 'lˌa',
						'Original' => 'là',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'lo?do?le(/ta1',
						'IPA' => 'lodolˈɛtɑ',
						'Original' => 'lọdọlé̜tα',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'AIS',
			'\\\\: la\\ lo?do?le(/ta1',
			[
				'class' => 'B',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '\\\\:',
						'IPA' => '',
						'Original' => ':',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => '',
						'Genus' => '',
						'Konzepte' => [
							779
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'la\\',
						'IPA' => 'lˌa',
						'Original' => 'là',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => ' ',
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							699
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					],
					[
						'Token' => 'lo?do?le(/ta1',
						'IPA' => 'lodolˈɛtɑ',
						'Original' => 'lọdọlé̜tα',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Bemerkung' => '',
						'Genus' => 'f',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [],
					'warnings' => []
				]
			]
		],
		
		[
			'TSA',
			'Nasn',
			[
				'class' => 'P',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'ger'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'TSA-Typ "Nasn"',
						'Genus' => '',
						'Konzepte' => [
							4
						],
						'Id_Tokengruppe' => NULL,
						'MTyp' => NULL,
						'PTyp' => 'NEW0'
					]
				],
				'global' => [
					'groups' => [],
					'mtypes' => [],
					'ptypes' => [
						[
							'Beta' => 'Nasn',
							'Original' => 'Nasn',
							'Quelle' => 'TSA'
						]
					],
					'warnings' => []
				]
			]
		],
		
		[
			'DizMT',
			'lemma111 lemma112 lemma113 / lemma211 ### lemma311 lemma312',
			[
				'class' => 'M',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			],
			[
				'tokens' => [
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'DizMT-Typ "lemma111"',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => 'NEW0',
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => 'DizMT-Typ "lemma112"',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => 'NEW1',
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 1,
						'Ebene_2' => 1,
						'Ebene_3' => 3,
						'Bemerkung' => 'DizMT-Typ "lemma113"',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW0',
						'MTyp' => 'NEW2',
						'PTyp' => NULL
					],
					[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 2,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'DizMT-Typ "lemma211"',
						'Genus' => '',
						'Konzepte' => [4],
						'Id_Tokengruppe' => NULL,
						'MTyp' => 'NEW4',
						'PTyp' => NULL
					],
										[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => ' ',
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => ' ',
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 1,
						'Bemerkung' => 'DizMT-Typ "lemma311"',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW1',
						'MTyp' => 'NEW5',
						'PTyp' => NULL
					],
										[
						'Token' => '',
						'IPA' => '',
						'Original' => '',
						'Trennzeichen' => NULL,
						'Trennzeichen_IPA' => NULL,
						'Trennzeichen_Original' => NULL,
						'Ebene_1' => 3,
						'Ebene_2' => 1,
						'Ebene_3' => 2,
						'Bemerkung' => 'DizMT-Typ "lemma312"',
						'Genus' => '',
						'Konzepte' => [],
						'Id_Tokengruppe' => 'NEW1',
						'MTyp' => 'NEW6',
						'PTyp' => NULL
					]
				],
				'global' => [
					'groups' => [
						[
							'Genus' => '',
							'Bemerkung' => 'DizMT-Typ "lemma111 lemma112 lemma113"',
							'Konzepte' => [
								4
							],
							'MTyp' => 'NEW3',
							'PTyp' => NULL
						],
						[
							'Genus' => '',
							'Bemerkung' => 'DizMT-Typ "lemma311 lemma312"',
							'Konzepte' => [
								4
							],
							'MTyp' => 'NEW7',
							'PTyp' => NULL
						]
					],
					'mtypes' => [
						[
							'Beta' => 'lemma111',
							'Orth' => 'lemma111',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma112',
							'Orth' => 'lemma112',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma113',
							'Orth' => 'lemma113',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma111 lemma112 lemma113',
							'Orth' => 'lemma111 lemma112 lemma113',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma211',
							'Orth' => 'lemma211',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma311',
							'Orth' => 'lemma311',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma312',
							'Orth' => 'lemma312',
							'Genus' => '',
							'Quelle' => 'DizMT'
						],
						[
							'Beta' => 'lemma311 lemma312',
							'Orth' => 'lemma311 lemma312',
							'Genus' => '',
							'Quelle' => 'DizMT'
						]
					],
					'ptypes' => [],
					'warnings' => []
				]
			]
		]
	];
	
	foreach ( $tests as $index => $test ) {
		$tokenizer = va_create_tokenizer ($test[0]);
		
		try {
			$res = $tokenizer->tokenize ($test[1], $test[2]);
			
			$comp = va_deep_assoc_array_compare ($test[3], $res);
			if ($comp === true) {
				echo 'Test ' . $index . ' successfull!<br />';
			} else {
				echo 'Error "' . $comp . '":<br />Result:<br />' . va_array_to_html_string ($res) . '<br />Expected:<br />' . va_array_to_html_string ($test[3]) . '<br /><br />';
			}
		}
		catch (Exception $e){
			echo 'Test ' . $index . ' created Exception: ' . $e->__toString() . '<br />';
		}
	}
	
	$tests_esception = [
		[
			'AIS',
			'a\\\\b',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'AIS',
			'test.a',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'ALD-I',
			'1a1b3c9x',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'BSA',
			'99',
			[
				'class' => 'B',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'ger'
			]
		],
		[
			'SDS',
			'NAse',
			[
				'class' => 'M',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'ger'
			]
		],
		[
			'SDS',
			'Ba.nane',
			[
				'class' => 'M',
				'concepts' => [
					5
				],
				'notes' => '',
				'lang' => 'ger'
			]
		],
		[
			'AIS',
			'test',
			[
				'class' => 'B',
				'concepts' => [],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'AIS',
			'test \\(de bah\\)',
			[
				'class' => 'B',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'AIS',
			'test\\(er\\)',
			[
				'class' => 'B',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			]
		],
		[
			'AIS',
			'bah <auch #1z//##>',
			[
				'class' => 'B',
				'concepts' => [4],
				'notes' => '',
				'lang' => 'rom'
			]
		]
	];
	
	foreach ( $tests_esception as $index => $test ) {
		$tokenizer = va_create_tokenizer ($test[0]);
		
		try {
			$res = $tokenizer->tokenize ($test[1], $test[2]);
			echo 'Error. No exception thrown for token "' . htmlentities($test[1]) . '" for source "' . $test[0] . '"<br /><br />';
		} catch ( TokenizerException $e ) {
			echo 'Test ' . ($index + count ($tests)) . ' successfull!<br />';
		}
	}
	
	echo '<br><br>';
	
	$parser = new VA_BetaParser ('AIS');
	echo '<pre>' . $parser->build_js_grammar_string (['UPPERCASE']) . '</pre>';
}
function va_tokenization_test_ajax(&$db) {
	$index = 0;
	$tokenizer = NULL;
	$lastSource = '';
	$limit = 500;
	
	$records = $db->get_results ('
			SELECT Id_Aeusserung, Aeusserung, Klassifizierung, a.Bemerkung, Erhebung, Sprache
			FROM Aeusserungen a JOIN Informanten USING (Id_Informant)
			WHERE Tokenisiert AND Not Nicht_Verifizieren AND (Verifiziert_Am IS NULL OR Geaendert_Am > Verifiziert_Am) AND Erhebung = "Clapie"
			ORDER BY Erhebung ASC, Id_Stimulus, Id_Informant
			LIMIT ' . $limit, ARRAY_A);
	
	while ( $index < count ($records) ) {
		
		$next_record = $records[$index];
		
		$tokensDB = va_tokenization_get_token_data_from_db ($next_record['Id_Aeusserung']);
		$concepts = array_map ('intval', $db->get_col ($db->prepare ('SELECT Id_Konzept FROM VTBL_Aeusserung_Konzept WHERE Id_Aeusserung = %d', $next_record['Id_Aeusserung'])));
		
		if ($tokenizer == NULL || $lastSource != $next_record['Erhebung']) {
			$tokenizer = va_create_tokenizer ($next_record['Erhebung']);
			$lastSource = $next_record['Erhebung'];
		}
		
		try {
			$tokensNew = va_tokenization_data_explicit ($tokenizer->tokenize ($next_record['Aeusserung'], [
				'class' => $next_record['Klassifizierung'],
				'concepts' => $concepts,
				'notes' => $next_record['Bemerkung'],
				'lang' => $next_record['Sprache']
			]));
		} catch ( Exception $e ) {
			echo $index . ' rows ok.<br><br>';
			echo 'Id_Record: ' . $next_record['Id_Aeusserung'] . ' --- ' . $e . '<br>';
			return;
		}
		
		if (count ($tokensDB) == count ($tokensNew)) {
			foreach ( $tokensDB as $i => $token ) {
				if ($token['Id_Tokengruppe'] && count ($token['Konzepte']) == 1) {
					if (count ($tokensNew[$i]['Konzepte']) == 1) {
						$conceptDB = $token['Konzepte'][0];
						$conceptNew = $tokensNew[$i]['Konzepte'][0];
						
						if ($conceptDB != $conceptNew) {
							$gramDB = $db->get_var ($db->prepare ('SELECT Grammatikalisch FROM Konzepte WHERE Id_Konzept = %d', $conceptDB));
							$gramNew = $db->get_var ($db->prepare ('SELECT Grammatikalisch FROM Konzepte WHERE Id_Konzept = %d', $conceptNew));
							
							if ($gramDB == '1' && $gramNew == '1') {
								$tokensDB[$i]['Konzepte'] = [
									'GRAM'
								];
								$tokensNew[$i]['Konzepte'] = [
									'GRAM'
								];
								$tokensNew[$i]['Genus'] = '';
								if (count ($tokensDB) > $i + 1)
									$tokensNew[$i + 1]['Genus'] = '';
							}
						}
					} else {
						// Remove manuelly added concepts for parts of groups
						$tokensDB[$i]['Konzepte'] = [];
					}
				}
			}
			
			$added = false;
			foreach ( $tokensDB as $i => $token ) {
				if ($token['Ebene_3'] != 0) {
					if ($added)
						$tokensDB[$i]['Id_Tokengruppe']['Bemerkung'] = $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'];
					continue;
				}
				
				if ($token['Id_Tokengruppe'] && $token['Id_Tokengruppe']['Bemerkung'] == '' && $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'] != '') {
					$sql = $db->prepare ('
						UPDATE Tokengruppen tg
						SET Bemerkung = %s
						WHERE Id_Tokengruppe = (SELECT DISTINCT Id_Tokengruppe FROM Aeusserungen JOIN Tokens USING (Id_Aeusserung) WHERE Id_Aeusserung = %d and Tokens.Ebene_1 = %d and Tokens.Ebene_2 = %d and Tokens.Ebene_3 = %d)', $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'], $next_record['Id_Aeusserung'], $token['Ebene_1'], $token['Ebene_2'], $token['Ebene_3']);
					echo $sql . '<br>';
					$db->query ($sql);
					
					$tokensDB[$i]['Id_Tokengruppe']['Bemerkung'] = $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'];
					$added = true;
				} else {
					$added = false;
				}
			}
		}
		
		$comp = va_deep_assoc_array_compare ($tokensDB, $tokensNew);
		
		if ($comp !== true) {
			echo $index . ' rows ok.<br><br>';
			
			echo 'Id_Record: ' . $next_record['Id_Aeusserung'] . ' --- ' . $comp . '<br>';
			
			echo 'Tokenized:<br>';
			echo va_array_to_html_string ($tokensNew, 0);
			echo '<br>';
			echo 'DB:<br>';
			echo va_array_to_html_string ($tokensDB, 0);
			echo '<br>';
			return;
		} else {
			$db->query ($db->prepare ('UPDATE Aeusserungen SET Verifiziert_Am = NOW() WHERE Id_Aeusserung = %d', $next_record['Id_Aeusserung']));
		}
		
		$index ++;
	}
	echo $index . ' records ok.<br />';
}
function va_tokenization_data_explicit($data) {
	foreach ( $data['tokens'] as $index => $token ) {
		if (isset ($token['MTyp'])) {
			if (strpos ($token['MTyp'], 'NEW') === 0) {
				$data['tokens'][$index]['MTyp'] = $data['global']['mtypes'][intval (substr ($token['MTyp'], 3))];
			} else {
				$data['tokens'][$index]['MTyp'] = va_tokenization_mtype_id_to_data ($token['MTyp']);
			}
		}
		
		if (isset ($token['PTyp'])) {
			if (strpos ($token['PTyp'], 'NEW') === 0) {
				$data['tokens'][$index]['PTyp'] = $data['global']['ptypes'][intval (substr ($token['PTyp'], 3))];
			} else {
				$data['tokens'][$index]['PTyp'] = va_tokenization_ptype_id_to_data ($token['PTyp']);
			}
		}
		
		if (isset ($token['Id_Tokengruppe'])) {
			$data['tokens'][$index]['Id_Tokengruppe'] = $data['global']['groups'][intval (substr ($token['Id_Tokengruppe'], 3))];
			
			if (isset ($data['tokens'][$index]['Id_Tokengruppe']['MTyp'])) {
				$data['tokens'][$index]['Id_Tokengruppe']['MTyp'] = va_tokenization_mtype_id_to_data ($data['tokens'][$index]['Id_Tokengruppe']['MTyp']);
			}
			
			if (isset ($data['tokens'][$index]['Id_Tokengruppe']['PTyp'])) {
				$data['tokens'][$index]['Id_Tokengruppe']['PTyp'] = va_tokenization_ptype_id_to_data ($data['tokens'][$index]['Id_Tokengruppe']['PTyp']);
			}
		}
	}
	return $data['tokens'];
}
function va_tokenization_get_token_data_from_db($id_record) {
	global $va_xxx;
	
	$tokens = $va_xxx->get_results ($va_xxx->prepare ('
		SELECT
			Token,
			t.IPA,
			t.Original,
			Trennzeichen,
			Trennzeichen_Original,
			Trennzeichen_IPA,
			Ebene_1,
			Ebene_2,
			Ebene_3,
			t.Bemerkung,
			t.Genus,
			GROUP_CONCAT(DISTINCT Id_Konzept) AS Konzepte,
			(SELECT p.Id_phon_Typ FROM VTBL_Token_phon_Typ v JOIN phon_Typen p USING (Id_phon_Typ) WHERE p.Quelle = s.Erhebung AND v.Id_Token = t.Id_Token) AS PTyp,
			(SELECT m.Id_morph_Typ FROM VTBL_Token_morph_Typ v JOIN morph_Typen m USING (Id_morph_Typ) WHERE m.Quelle = s.Erhebung AND v.Id_Token = t.Id_Token) AS MTyp,
			Id_Tokengruppe
		FROM
			Tokens t
			JOIN Stimuli s USING (Id_Stimulus)
			LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
		WHERE Id_Aeusserung = %d
		GROUP BY t.Id_Token
		ORDER BY Ebene_1 ASC, Ebene_2 ASC, Ebene_3 ASC', $id_record), ARRAY_A);
	
	foreach ( $tokens as $key => $token ) {
		if ($tokens[$key]['Konzepte']) {
			$tokens[$key]['Konzepte'] = array_map ('intval', explode (',', $token['Konzepte']));
		} else {
			$tokens[$key]['Konzepte'] = [];
		}
		
		$token = $tokens[$key];
		
		$tokens[$key]['Ebene_1'] = intval ($tokens[$key]['Ebene_1']);
		$tokens[$key]['Ebene_2'] = intval ($tokens[$key]['Ebene_2']);
		$tokens[$key]['Ebene_3'] = intval ($tokens[$key]['Ebene_3']);
		
		if ($token['Id_Tokengruppe']) {
			$tokens[$key]['Id_Tokengruppe'] = va_tokenization_token_group_id_to_data ($token['Id_Tokengruppe']);
		}
		
		if ($token['MTyp']) {
			$tokens[$key]['MTyp'] = va_tokenization_mtype_id_to_data ($token['MTyp']);
		}
		
		if ($token['PTyp']) {
			$tokens[$key]['PTyp'] = va_tokenization_ptype_id_to_data ($token['PTyp']);
		}
	}
	
	return $tokens;
}
function va_tokenization_token_group_id_to_data($id_group) {
	global $va_xxx;
	
	$res = $va_xxx->get_row ($va_xxx->prepare ('
		SELECT
			tg.Genus,
			tg.Bemerkung,
			GROUP_CONCAT(DISTINCT Id_Konzept) AS Konzepte,
			(SELECT p.Id_phon_Typ FROM phon_Typen p JOIN VTBL_Tokengruppe_phon_Typ v USING (Id_phon_Typ) WHERE p.Quelle = s.Erhebung AND tg.Id_Tokengruppe = v.Id_Tokengruppe) AS PTyp,
			(SELECT m.Id_morph_Typ FROM morph_Typen m JOIN VTBL_Tokengruppe_morph_Typ v USING (Id_morph_Typ) WHERE m.Quelle = s.Erhebung AND tg.Id_Tokengruppe = v.Id_Tokengruppe) AS MTyp
		FROM
			Tokengruppen tg
			JOIN Tokens USING (Id_Tokengruppe)
			JOIN Stimuli s USING (Id_Stimulus)
			LEFT JOIN VTBL_Tokengruppe_Konzept v3 ON v3.Id_Tokengruppe = tg.Id_Tokengruppe
		WHERE tg.Id_Tokengruppe = %d
		GROUP BY tg.Id_Tokengruppe
		ORDER BY Ebene_1 ASC, Ebene_2 ASC, Ebene_3 ASC
		', $id_group), ARRAY_A);
	
	if ($res['Konzepte']) {
		$res['Konzepte'] = array_map ('intval', explode (',', $res['Konzepte']));
	} else {
		$res['Konzepte'] = [];
	}
	
	if ($res['MTyp']) {
		$res['MTyp'] = va_tokenization_mtype_id_to_data ($res['MTyp']);
	}
	
	if ($res['PTyp']) {
		$res['PTyp'] = va_tokenization_ptype_id_to_data ($res['PTyp']);
	}
	
	return $res;
}
function va_tokenization_ptype_id_to_data($id_type) {
	global $va_xxx;
	
	return $va_xxx->get_row ($va_xxx->prepare ('SELECT Beta, Quelle FROM phon_Typen WHERE Id_phon_Typ = %d', $id_type), ARRAY_A);
}
function va_tokenization_mtype_id_to_data($id_type) {
	global $va_xxx;
	
	return $va_xxx->get_row ($va_xxx->prepare ('SELECT Orth, Genus, Quelle FROM morph_Typen WHERE Id_morph_Typ = %d', $id_type), ARRAY_A);
}
function va_check_tokenizer() {
	echo va_tokenize_test ();
	echo '<br /><br />';
	echo '<input type="button" value="Check tokenized data" id="va_tok_check_button" />';
	echo '<div id="va_tok_check_result"></div>';
	
	?>
<script type="text/javascript">
		jQuery(function (){
			jQuery("#va_tok_check_button").click(function (){
				jQuery("#va_tok_check_result").html("");
				jQuery.post(ajaxurl, {
					"action": "va",
					"namespace": "util",
					"query" : "check_tokenizer"
				},
				function (response){
					jQuery("#va_tok_check_result").html(response);
				});
			});
		});
	</script>

<?php
	
	echo '<br /><br />';
}
?>