<?php
	return [
		'logIn' => [
			'nomUsuario' => [
				'constraint' => ['required' => true],
				'message' => 'Debe ingresar el nombre del usuario.'
			],
			'contrasena' => [
				'constraint' => ['required' => true],
				'message' => 'Debe ingresar la contraseña.'
			]
		]
	];
?>