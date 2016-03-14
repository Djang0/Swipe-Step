<?php
// Routes

//$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    //$this->logger->info("Slim-Skeleton '/' route");

    // Render index view
  //  return $this->renderer->render($response, 'index.phtml', $args);
//});

$app->get('/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $data['message'] = 'Nothing to see here!';
    //$data['id']='2';
    $body->write(json_encode($data));
});
$app->get('/dump/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = $request->getHeaders();

    //$data['id']='2';
    $body->write(json_encode($data));
});
$app->get('/getTargets/', function ($request, $response, $args) {

    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    try {
        $id = $response->getHeaderLine('X-Owner');
        $db = getDB();
        $sth = $db->prepare('SELECT targets.id, targets.stamp_created, targets.url, targets.code  FROM targets,owners WHERE targets.owner_id = owners.id AND owners.id = :owner_id');
        $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);

        $sth->execute();
        $target = $sth->fetchAll(PDO::FETCH_ASSOC);

        $response = $response->withStatus(200);
        $body->write('{"hits":'.json_encode($target).'}');

        $db = null;
    } catch (PDOException $e) {
        $response->withStatus(404);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
});
$app->get('/getHits/{target_id}', function ($request, $response, $args) {

    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    try {
        $id = $response->getHeaderLine('X-Owner');
        $target_id = $args['target_id'];
        $db = getDB();
        if (!is_null($target_id) and is_int($target_id) and $target_id > 0) {
            $sth = $db->prepare('SELECT hits.id, hits.stamp, hits.ip, hits.referrer, targets.url, targets.code  FROM hits,targets,owners WHERE hits.target_id = targets.id AND targets.owner_id = owners.id AND owners.id = :owner_id AND targets.id = :target_id ');
            $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
            $sth->bindParam(':target_id', $target_id, PDO::PARAM_INT);
        } else {
            $sth = $db->prepare('SELECT hits.id, hits.stamp, hits.ip, hits.referrer, targets.url, targets.code  FROM hits,targets,owners WHERE hits.target_id = targets.id AND targets.owner_id = owners.id AND owners.id = :owner_id');
            $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
        }
        $sth->execute();
        $hits = $sth->fetchAll(PDO::FETCH_ASSOC);
        $response = $response->withStatus(200);
        $body->write('{"hits":'.json_encode($hits).'}');
        $db = null;
    } catch (PDOException $e) {
        $response->withStatus(404);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
});

//keep referer in params
$app->get('/to/{code}', function ($request, $response, $args) {

    $response = $response->withHeader('Content-type', 'text/html');
    $body = $response->getBody();
    try {
        $code = $args['code'];
        if (!is_null($code) and is_string($code) and $code != '') {
            $db = getDB();
            $sth = $db->prepare('select * from targets where code = :code');
            $sth->bindParam(':code', $code, PDO::PARAM_STR);

            $sth->execute();
            $target = $sth->fetch(PDO::FETCH_OBJ);

            if ($target) {
                $ipAddress = $request->getAttribute('ip_address');
                $referrer = $request->getHeaderLine('HTTP_REFERER');
                $sth = $db->prepare('insert into hits (target_id, ip,referrer) VALUES (:target_id, :ip, :ref)');
                $sth->bindParam(':target_id', $target->id, PDO::PARAM_INT);
                $sth->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
                $sth->bindParam(':ref', $referrer, PDO::PARAM_STR);
                $sth->execute();

                $response = $response->withStatus(301);
                header('Location: '.$target->url.'?referrer='.urlencode($referrer), true, 301);
            } else {
                $response = $response->withStatus(404);
                return $this->renderer->render($response, 'index.phtml', $args);
            }
            $db = null;
        } else {
            $response = $response->withStatus(404);
            return $this->renderer->render($response, 'index.phtml', $args);
        }
    } catch (PDOException $e) {
        $response->withStatus(404);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
});
$app->get('/addTarget/{code}/{url}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    try {
        $id = $response->getHeaderLine('X-Owner');
        $code = $args['code'];
        $url = $args['url'];

        $db = getDB();
        $sth = $db->prepare('INSERT INTO `clicktrax`.`targets` (`id`, `stamp_created`, `code`, `url`, `owner_id`) VALUES (NULL, CURRENT_TIMESTAMP, :code, :url, :id)');
        $sth->bindParam(':code', $code, PDO::PARAM_STR);
        $sth->bindParam(':url', $url, PDO::PARAM_STR);
        $sth->bindParam(':id', $id, PDO::PARAM_INT);
        $sth->execute();
        $response->withStatus(200);
        $body->write('{"Success":{"msg":"Added target"}}');
        $db = null;
    } catch (PDOException $e) {
        $response->withStatus(404);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }

}

);
