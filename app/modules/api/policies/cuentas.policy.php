<?php
	return [
		'asociarCuenta' => [
			'idBanco' => [
				'constraint' => ['required' => true],
				'message' => 'Debe especificar el banco.'
			],
			'numCuenta' => [
				'constraint' => ['required' => true],
				'message' => 'Debe especificar el número de la cuenta.'
			]
		]
	];
?>