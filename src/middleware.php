<?php
// Application middleware

$checkProxyHeaders = true; // Note: Never trust the IP address for security processes!
$trustedProxies = []; // Note: Never trust the IP address for security processes!
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));

$app->add(function ($request, $response, $next) {

                $api_key = $request->getHeaderLine('X-API-KEY');
                $body = $response->getBody();
                $pth = substr($request->getUri()->getPath(), 0, 4);
                $pth2 = substr($request->getUri()->getPath(), 0, 6);
                if ($pth == '/to/' or $pth2 == '/from/' or $pth2 == '/dump/') {
                    $response = $response->withHeader('X-Owner', $owner->id);
                    $response = $response->withHeader('X-Authenticated', 'True');
                    $response = $response->withStatus(200);
                    $response = $next($request, $response);
                } else {
                    if ($api_key == null) {
                        $response = $response->withHeader('X-Authenticated', 'False');
                        $response = $response->withStatus(401);

                        $body->write('{"error":{"text": "api key not sent" }}');

                        $authorized = false;
                    } else {
                        try {
                            $db = getDB();
                            $sth = $db->prepare('SELECT * FROM owners WHERE api_key = :key');
                            $sth->bindParam(':key', $api_key, PDO::PARAM_STR);
                            $sth->execute();

                            $owner = $sth->fetch(PDO::FETCH_OBJ);

                            if ($owner) {
                                //$this->logger->info("fetch ".$owner->name);
                                    $response = $response->withHeader('X-Owner', $owner->id);
                                $response = $response->withHeader('X-Authenticated', 'True');
                                $response = $response->withStatus(200);
                                $response = $next($request, $response);
                            } else {
                                $response = $response->withHeader('X-Authenticated', 'False');
                                $response = $response->withStatus(403);

                                $body->write('{"error":{"text": "api key invalid" }}');
                            }
                            $db = null;
                        } catch (PDOException $e) {
                            $response()->setStatus(404);
                            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
                        }
                    }
                }

                return $response;

 });

// e.g: $app->add(new \Slim\Csrf\Guard);
