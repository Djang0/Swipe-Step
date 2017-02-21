<?php
// Application middleware

$checkProxyHeaders = true; // Note: Never trust the IP address for security processes!
$trustedProxies = []; // Note: Never trust the IP address for security processes!
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));

// Authentication middelware. Searches for an owner that has an api_key corresponding to the one stored in the custom X-API-KEY request header.
// Adds a X-Owner = owner.id response header and a X-Authenticated= ('True' or 'False') response header
// Method : ALL
// (200) => authenticated
// (401) => api key not sent or invalid
// (403) => Unauthenticated request
// (503) => PDOException
$app->add(function ($request, $response, $next) {

                $api_key = $request->getHeaderLine('X-API-KEY');
                $body = $response->getBody();
                $pth = substr($request->getUri()->getPath(), 0, 4);
                $pth2 = substr($request->getUri()->getPath(), 0, 6);
                // filtering No authentication required verbs
                if ($pth == '/to/' or $pth2 == '/from/' or $pth2 == '/dump/' or $pth2='/hook/') {
                    $response = $response->withHeader('X-Owner', '0');
                    $response = $response->withHeader('X-Authenticated', 'True');
                    $response = $response->withStatus(200);
                    $response = $next($request, $response);
                } else {
                    if ($api_key == null or strlen($api_key)>32 or strlen($api_key)<32) {
                        // Invalid api_key
                        $response = $response->withHeader('X-Authenticated', 'False');
                        $response = $response->withStatus(401);
                        $body->write('{"error":{"msg": "Api key not sent or invalid" }}');
                        $authorized = false;
                    } else {
                        try {
                            $db = getDB();
                            $sth = $db->prepare('SELECT * FROM owners WHERE api_key = :key');
                            $sth->bindParam(':key', $api_key, PDO::PARAM_STR);
                            $sth->execute();

                            $owner = $sth->fetch(PDO::FETCH_OBJ);
                            if ($owner) {
                              // Authenticated request
                                $response = $response->withHeader('X-Owner', $owner->id);
                                $response = $response->withHeader('X-Authenticated', 'True');
                                $response = $response->withStatus(200);
                                $response = $next($request, $response);
                            } else {
                              // Unauthenticated request
                                $response = $response->withHeader('X-Authenticated', 'False');
                                $response = $response->withStatus(403);
                                $body->write('{"error":{"msg": "Unauthenticated request" }}');
                            }
                            $db = null;
                        } catch (PDOException $e) {
                            $response()->setStatus(503);
                            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
                        }
                    }
                }

                return $response;

 });

// e.g: $app->add(new \Slim\Csrf\Guard);
