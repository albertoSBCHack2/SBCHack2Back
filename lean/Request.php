<?php

class Request {
    private $baseURI;
    private $uri;
    private $verb;
    private $headers;
    private $body;
    private $query;
    private $params;
    private $tokenData;

    public function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->uri = strtok( $this->uri, '?' ); // Quitamos el query string params.
        $this->verb = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        parse_str($_SERVER['QUERY_STRING'] ?? null, $this->query);
        $body = file_get_contents('php://input') ?? null;
        $this->body = json_decode($body, true);
    }

    // Setter / Getter for: $uri;
    public function getUri()
    {
        return $this->uri;
    }

    public function setBaseURI( $baseURI ) 
    {
        $this->baseURI = $baseURI;
    }

    public function getBaseURI()
    {
        return $this->baseURI;
    }

    // Setter / Getter for: $verb;
    public function getVerb()
    {
        return $this->verb;
    }

    // Setter / Getter for: $verb;
    public function getHeader($key = null)
    {
        return $key 
            ? ( 
                isset( $this->headers[ $key ] ) 
                    ? $this->headers[ $key ]
                    : (
                        isset( $this->headers[ strtolower($key) ] )
                            ? $this->headers[ strtolower($key) ]
                            : null
                    )
            )
            : $this->headers;
    }

    public function setHeader($key, $value)
    {
        $this->headers[ $key ] = $value;
    }

    // Setter / Getter for: $body;
    public function getBody($key = null)
    {
        return $key ? ( isset( $this->body[$key] ) ? $this->body[$key] : null ) : $this->body;
    }

    // Setter / Getter for: $query;
    public function getQuery($key = null)
    {
        return $key ? ( isset( $this->query[$key] ) ? $this->query[$key] : null ) : $this->query;
    }

    // Setter / Getter for: $params;
    public function getParams($key = null)
    {
        return $key ? ( $this->params[$key] ?? null ) : $this->params;
    }

    // Get all params
    public function getAllParams()
    {
        $params = $this->body ? $this->body : array();
        $params = array_merge( $params, $this->query ? $this->query : array() );
        $params = array_merge( $params, $this->params ? $this->params : array() );

        return $params;
    }

    public function setParams($value)
    {
        $this->params = $value;
    }

    // $tokenData SETTER/GETTER
    public function getTokenData($key = null)
    {
        return $key ? ($this->tokenData[$key] ?? null) : $this->tokenData;
    }

    public function setTokenData($value)
    {
        $this->tokenData = $value;
    }
}
