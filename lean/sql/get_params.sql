--PostgreSQL

CREATE OR REPLACE FUNCTION lean_get_params( p_sp VARCHAR )
	RETURNS SETOF lean_typ_get_params AS
$$
DECLARE 
	rec lean_typ_get_params;
begin
	/***
		Autor: Gil Verduzco
		Descripción: Función para obtener los parámetros de las funciones.
	***/

	/*
		SELECT * FROM lean_get_params('fun_re_areas_listar')
			
		CREATE TYPE lean_typ_get_params AS
		(
			param_list TEXT
		)

		DROP TYPE lean_typ_get_params CASCADE;
	*/

	FOR rec IN
		SELECT pg_catalog.pg_get_function_identity_arguments( p.oid ) AS param_list
		FROM pg_catalog.pg_proc p

		INNER JOIN pg_catalog.pg_namespace n ON n.oid = p.pronamespace

		WHERE n.nspname = 'public'
		AND proname = p_sp			
	LOOP
		RETURN NEXT rec;
	END LOOP;
END;
$$
  LANGUAGE plpgsql

-- SQLServer

CREATE PROCEDURE lean_get_params
	@p_sp VARCHAR(MAX)
AS
BEGIN
	/***
		Autor: Gil Verduzco
		Descripción: Función para obtener los parámetros de las funciones.
	***/
	
	SELECT REPLACE( name, '@', '' ) + ' ' + TYPE_NAME(user_type_id) AS param_list
	FROM sys.parameters 
	WHERE OBJECT_ID = OBJECT_ID( @p_sp );
END