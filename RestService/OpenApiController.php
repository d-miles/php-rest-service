<?php

namespace RestService;

/**
 * OpenAPI Controller
 */
class OpenApiController {
    
    public function __construct(
        protected readonly Server $server,
        protected readonly array $apiSpec,
    ) { }
    
    /**
     * Swagger (OpenAPI) Index page
     * 
     * This is a copy of index.html from the npm package swagger-ui-dist, with the following
     * modifications:
     *      - Use a CDN instead of local files
     *      - Add an inline version of swagger-initializer.js
     *      - Load the OpenAPI specification from /openapi.json (see swagger-initializer.js) (see static::getOpenApi())
     *      - Remove favicons
     *      - Remove the download URL wrapper
     *      - Prevent the user from interacting with the server dropdown
     * 
     * @openapi-ignore
     * @return string swagger-ui-dist
     */
    public function get() {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>{$this->apiSpec['title']} {$this->apiSpec['version']} - Swagger UI</title>
            <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
            <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/index.css" />
            <style>
            #swagger-ui .download-url-wrapper {
                display: none;
            }
            .servers select {
                pointer-events: none;
                appearance: none;
                background-image: none;
                padding-right: 0;
            }
            </style>
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js" charset="UTF-8"></script>
            <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
            <script>
            window.onload = () => {
                window.ui = SwaggerUIBundle({
                    url: '/openapi.json',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                    layout: 'StandaloneLayout',
                });
            };
            </script>
        </body>
        </html>
        HTML;
    }
    
    /**
     * Get the OpenAPI specification. According to the OpenAPI specification, this file should be
     * served as `openapi.json`.
     * 
     * @url openapi
     * @openapi-url /openapi.json
     */
    public function getOpenApi() {
        $this->server->getClient()
                    ->setCustomFormat($this->server->getClient()->asJSON(...));
        
        // Define API details
        $spec = [
            'openapi' => '3.0.0',
            'info' => array_merge($this->apiSpec['info'], 
                                array_filter($this->apiSpec, 
                                            fn($key) => !in_array($key, ['version', 'title', 'description'], 
                                            ARRAY_FILTER_USE_KEY))),
        ];
        if (isset($this->apiSpec['description']) && $this->apiSpec['description'] != null) {
            $spec['info']['description'] = $this->apiSpec['description'];
        }
        if (isset($this->apiSpec['server']) && $this->apiSpec['server'] != null) {
            $spec['servers'] = [
                ['url' => $this->apiSpec['server']]
            ];
        }
        
        $routes = $this->apiSpec['recurse']
                    ? $this->getControllerRoutes($this->server)
                    : $this->generateOpenApiRoutes($this->server);
        
        ksort($routes);
        
        // Add the routes to the spec
        $spec['paths'] = $routes;
        
        // Add components section
        $spec['components'] = [
            'schemas' => [
                '500' => [
                    'type' => 'object', 
                    'properties' => [
                        'status' => ['type' => 'integer'], 
                        'error' => ['type' => 'string'], 
                        'message' => ['type' => 'object']
                    ]
                ],
                'AnyValue' => null
            ]
        ];
        
        return $spec;
    }
    
    /**
     * Recursively retrieve routes for a controller and its sub-controllers.
     * 
     * @param  Server $server   The controller object to retrieve routes for.
     * @return array
     */
    protected function getControllerRoutes(Server $server) {
        $routes = $this->generateOpenApiRoutes($server);
        foreach ($server->getSubControllers() as $subController) {
            $routes = array_merge($routes, $this->getControllerRoutes($subController));
        }
        return $routes;
    }
    
    /**
     * Generate route array for OpenAPI specification from the provided Server controller.
     * 
     * @param  Server $server   The controller object to generate routes for.
     * @return array            The generated route array.
     */
    protected function generateOpenApiRoutes(Server $server) {
        $url = $server->getTriggerUrl();
        $uri = null;
        $format = $this->server->getClient()->getOutputFormat();
        $routes = [];
        
        // Loop through routes and add to specification
        foreach ($server->getRoutes() as $routeUri => $routeMethods) {
            
            $matches = [];
            
            if (!$uri || ($uri && preg_match('|^'.$routeUri.'$|', $uri, $matches))) {
                
                if ($matches) {
                    array_shift($matches);
                }
                $path = $url . '/'.$routeUri;
                $originalPath = $path;
                $finalPath = null;
                $def = [];
                
                foreach ($routeMethods as $requestMethod => $phpMethod) {
                    if (is_string($phpMethod)) {
                        $ref = new \ReflectionClass($server->getController());
                        $refMethod = $ref->getMethod($phpMethod);
                    } else {
                        $refMethod = new \ReflectionFunction($phpMethod);
                    }
                    $metadata = $this->server->getMethodMetaData($refMethod);
                    if (array_key_exists('openapi-ignore', $metadata))
                        continue;
                    
                    // If URL provided in @openapi-url comment, use it
                    if (array_key_exists('openapi-url', $metadata)) {
                        $finalPath = $metadata['openapi-url'];
                    }
                    else {
                        preg_match_all('~ \( (?: [^()]+ | (?R) )*+ \) ~x', $originalPath, $paramMatches);
                        $place = 0;
                        
                        if (count($paramMatches[0] ?? []) > 0) {
                            foreach ($paramMatches[0] as $match) {
                                $param = array_keys($metadata['parameters'])[$place];
                                $replace = "{{$param}}";
                                if (($pos = strpos($originalPath, $match)) !== false) {
                                    if (substr($match, 0, 2) !== '(!' && substr($match, 0, 3) !== '(?!'  && substr($match, 0, 3) !== '(?:') {
                                        $path = substr_replace($originalPath, $replace, $pos, strlen($match));
                                        $metadata['parameters'][$param] = array_merge($metadata['parameters'][$param], ['in' => 'path']);
                                        $place++;
                                    }
                                }
                            }
                        }
                        
                        $finalPath = $path;
                        
                        preg_match_all('~ \( (?: [^()]+ | (?R) )*+ \) ~x', $finalPath, $paramMatches);
                        $place = 0;
                        
                        if (count($paramMatches[0] ?? []) > 0) {
                            foreach ($paramMatches[0] as $match) {
                                $param = array_keys($metadata['parameters'])[$place];
                                if (($pos = strpos($finalPath, $match)) !== false) {
                                    if (substr($match, 0, 2) === '(!' || substr($match, 0, 3) === '(?!' || substr($match, 0, 3) === '(?:') {
                                        $finalPath = substr_replace($finalPath, '', $pos, strlen($match));
                                    }
                                }
                            }
                        }
                        
                    }
                    
                    $type = $this->convertType($metadata['return']['type']);
                    $parameters = [];
                    $body = [];
                    foreach ($metadata['parameters'] as $name => $parameter) {
                        $paramType = $this->convertType($parameter['type']);
                        
                        if (isset($parameter['in']) && $parameter['in'] == 'path') {
                            $key = 'parameters';
                            $parameters[] = [
                                'in' => 'path',
                                'name' => $name, 
                                'required' => true,
                                'schema' => $paramType
                            ];
                        } else if (in_array($requestMethod, ['get', 'delete'])) {
                            $key = 'parameters';
                            $parameters[] = [
                                'in' => 'query',
                                'name' => $name, 
                                'required' => $parameter['required'] ?? false,
                                'schema' => $paramType
                            ];
                        } else {
                            $key = 'body';
                            $body[] = [
                                'in' => 'body',
                                'name' => $name,
                                'required' => $parameter['required'] ?? false,
                                'schema' => $paramType
                            ];
                        }
                        if ($parameter['description'] ?? null !== null) {
                            $$key['description'] = $parameter['description'];
                        }
                    }
                    $outputFormat = $this->convertFormat($format);
                    $def[$requestMethod] = [
                        'parameters' => $parameters,
                        'responses' => [
                            '200' => [
                                'description' => 'Successful operation',
                                'content' => [
                                    $outputFormat => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => [
                                                    'type' => 'integer',
                                                ],
                                                'data' => $type,
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '500' => [
                                'description' => 'Internal Server Error',
                                'content' => [
                                    $outputFormat => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/500'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    if (!in_array($requestMethod, ['get', 'delete'])) {
                        $requiredParams = [];
                        $properties = [];
                        $bodyRequired = false;
                        foreach ($body as $b) {
                            if ($b['required']) {
                                $bodyRequired = true;
                                $requiredParams[] = $b['name'];
                            }
                            $properties[$b['name']] =  $b['schema'];
                        }
                        if ($bodyRequired) {
                            $def[$requestMethod]['requestBody']['required'] = true;
                        }
                        $responseSchema = ['schema' => ['type' => 'object', 'properties' => $properties]];
                        $def[$requestMethod]['requestBody']['content'] = [
                            'application/json' => $responseSchema,
                            'application/x-www-form-urlencoded' => $responseSchema
                        ];
                    }
                }
                
                // Add route to spec
                if ($finalPath !== null)
                    $routes[$finalPath] = $def;
            }
        }
        return $routes;
    }
    
    /**
     * Converts PHP types to OpenAPI types.
     * 
     * @param  string $input The input to parse.
     * @return string        The converted type
     * 
     */
    private function convertType($input) {
        return match ($input) {
            'int'       => ['type' => 'integer'],
            'integer'   => ['type' => 'integer'],
            'bool'      => ['type' => 'boolean'],
            'boolean'   => ['type' => 'boolean'],
            'string'    => ['type' => 'string'],
            'number'    => ['type' => 'number'],
            'array'     => ['type' => 'array', 'items' => (object) null],
            'object'    => ['type' => 'object'],
            default     => ['$ref' => '#/components/schemas/AnyValue']
        };
    }
    
    /**
     * Converts a file extension format to a corresponding response/MIME type.
     * 
     * @param string $format The file format to convert.
     * @return string
     */
    private function convertFormat($format) {
        return match ($format) {
            'json'  => 'application/json',
            'xml'   => 'application/xml',
            'text'  => 'text/plain',
            default => 'application/json',
        };
    }
    
}