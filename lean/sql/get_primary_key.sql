-- PostgreSQL

CREATE OR REPLACE FUNCTION lean_get_primary_key(p_db VARCHAR, p_table VARCHAR)
	RETURNS SETOF lean_type_get_primary_key AS
$$
DECLARE 
	rec lean_type_get_primary_key;
BEGIN
	/***
		Autor: Gil Verduzco
		Descripción: Función para obtener el primary key de las tablas.
	***/

	/*
		SELECT * FROM lean_get_primary_key( null, null )
		
		CREATE TYPE lean_type_get_primary_key AS
		(
			column_name VARCHAR
		) 

		DROP TYPE lean_type_get_primary_key CASCADE
	*/

	FOR rec IN
		SELECT c.column_name
		FROM information_schema.columns c

		INNER JOIN information_schema.key_column_usage kcu ON c.table_catalog = kcu.constraint_catalog
			AND c.table_name = kcu.table_name
			AND c.column_name = kcu.column_name

		INNER JOIN information_schema.table_constraints tc ON kcu.constraint_catalog = tc.constraint_catalog
			AND kcu.table_name = tc.table_name
			AND kcu.constraint_name = tc.constraint_name

		WHERE c.table_catalog = p_db
		/*(
		    ( p_db_type = 'MySQL' AND c.table_schema = p_db ) OR
		    ( ( p_db_type = 'PostgreSQL' OR p_db_type = 'SQLServer' ) AND c.table_catalog = p_db )
		)*/
		AND c.table_name = p_table
		AND tc.constraint_type = 'PRIMARY KEY'
		
		ORDER BY c.ordinal_position
		
	LOOP
		RETURN NEXT rec;
	END LOOP;
END;
$$
  LANGUAGE plpgsql

-- SQLServer

CREATE PROCEDURE lean_get_primary_key
	@p_db VARCHAR(MAX), 
	@p_table VARCHAR(MAX)
AS
BEGIN
	SELECT c.column_name
	FROM information_schema.columns c

	INNER JOIN information_schema.key_column_usage kcu ON c.table_catalog = kcu.constraint_catalog
		AND c.table_name = kcu.table_name
		AND c.column_name = kcu.column_name

	INNER JOIN information_schema.table_constraints tc ON kcu.constraint_catalog = tc.constraint_catalog
		AND kcu.table_name = tc.table_name
		AND kcu.constraint_name = tc.constraint_name

	WHERE c.table_catalog = @p_db
	AND c.table_name = @p_table
	AND tc.constraint_type = 'PRIMARY KEY'

	ORDER BY c.ordinal_position
END