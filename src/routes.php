<?php
// Routes

// No Authentication required.
// Method : GET
// (200) Nothing to see here !
// 'Content-type'='application/json'
$app->get('/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $data['message'] = 'Nothing to see here!';
    $response = $response->withStatus(200);
    $body->write(json_encode($data));
});

// No Authentication required.
// Method : GET
// (200) Dumps request for debug purpose.
// 'Content-type'='application/json'
$app->get('/dump/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = $request->getHeaders();
    $response = $response->withStatus(200);
    $body->write(json_encode($data));
});

// Authentication required. Returns all target specifications owned by the authenticated user.
// Method : GET
// (200) => Ok
// (503) => PDOException
// 'Content-type'='application/json'
$app->get('/getCodes/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $date = date_create();
    try {
        $id = $response->getHeaderLine('X-Owner');
        $db = getDB();
        $sth = $db->prepare('SELECT targets.id, targets.stamp_created, targets.url, targets.code  FROM targets,owners WHERE targets.owner_id = owners.id AND owners.id = :owner_id');
        $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);

        $sth->execute();
        $targets = $sth->fetchAll(PDO::FETCH_ASSOC);
        $data['result'] = array(
          'timestamp' => date_format($date, 'd-m-Y H:i:s'),
          'code_count' => count($targets),
          'codes' => $targets,
      );
        $response = $response->withStatus(200);
        $body->write(json_encode($data));
        $db = null;
    } catch (PDOException $e) {
        $response->withStatus(503);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
});
// Authentication required. Returns all hits owned by the authenticated user and grouped by target.
// Method : GET
// (200) => Ok
// (503) => PDOException
// 'Content-type'='application/json'
$app->get('/getAllHits/', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $date = date_create();
    $id = $response->getHeaderLine('X-Owner');
        try {
            $db = getDB();
            $sth = $db->prepare('SELECT targets.id, targets.stamp_created, targets.code, targets.url FROM targets,owners WHERE targets.owner_id = owners.id AND owners.id = :owner_id');
            $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
            $sth->execute();
            $targets = $sth->fetchAll(PDO::FETCH_ASSOC);
            $i = 0;
            foreach ($targets as $target) {
                $target_id = intval($target['id']);
                $sth = $db->prepare('SELECT hits.id, hits.stamp, hits.ip, hits.referrer  FROM hits,targets WHERE hits.target_id = targets.id AND targets.id = :target_id ');
                $sth->bindParam(':target_id', $target_id, PDO::PARAM_INT);
                $sth->execute();
                $hits = $sth->fetchAll(PDO::FETCH_ASSOC);
                $targets[$i]['hit_count'] = count($hits);
                $targets[$i]['hits'] = $hits;
                $i = $i + 1;
            }
            $data['result'] = array(
              'timestamp' => date_format($date, 'd-m-Y H:i:s'),
              'target_count' => count($targets),
              'target' => $targets,
          );
            $response = $response->withStatus(200);
            $body->write(json_encode($data));
            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
});

// Authentication required. Returns all hits for a given target owned by the authenticated user.
// Parameter : targets.id (integer not null >0)
// Method : GET
// (200) => Ok
// (503) => PDOException
// 'Content-type'='application/json'
$app->get('/getTarget/{target_id}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $date = date_create();
    $id = $response->getHeaderLine('X-Owner');
    $target_id = intval($args['target_id']);
    if (!is_null($target_id) and is_int($target_id) and $target_id > 0) {
        try {
            $db = getDB();
            $sth = $db->prepare('SELECT targets.id, targets.stamp_created, targets.code, targets.url FROM targets,owners WHERE targets.owner_id = owners.id AND owners.id = :owner_id AND targets.id = :target_id ');
            $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
            $sth->bindParam(':target_id', $target_id, PDO::PARAM_INT);
            $sth->execute();
            $target = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (count($target) == 1) {
                $sth = $db->prepare('SELECT hits.id, hits.stamp, hits.ip, hits.referrer  FROM hits,targets WHERE hits.target_id = targets.id AND targets.id = :target_id ');
                $sth->bindParam(':target_id', $target_id, PDO::PARAM_INT);
                $sth->execute();
                $hits = $sth->fetchAll(PDO::FETCH_ASSOC);
                $target[0]['hit_count'] = count($hits);
                $target[0]['hits'] = $hits;
            }
            $data['result'] = array(
              'timestamp' => date_format($date, 'd-m-Y H:i:s'),
              'target' => $target[0],
          );
            $response = $response->withStatus(200);
            $body->write(json_encode($data));
            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
    } else {
        $response->withStatus(200);
        $body->write(json_encode($data));
    }

});
// Authentication required. Adds a target owned by the authenticated user.
// Method : GET
// Parameter 1 : targets.code (String != '' and not null max 32 char)
// Parameter 2 : targets.url (String != '' and not null must be urlencoded max 2083 char)
// (200) => Ok
// (503) => PDOException
// (400) => Failure. code AND / OR url is not properly formated. | Code already exists
// 'Content-type'='application/json'
$app->get('/addTarget/{code}/{url}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $id = $response->getHeaderLine('X-Owner');
    $code = $args['code'];
    $url = $args['url'];
    if(strlen($code)<=32 and strlen($code)>0 and strlen($url)<=2083 and strlen($url)>0){
      try {
          $db = getDB();
          $sth = $db->prepare('select * from targets where code= :code');
          $sth->bindParam(':code', $code, PDO::PARAM_STR);
          $sth->execute();
          $codes= $sth->fetchAll(PDO::FETCH_ASSOC);
          if (count($codes)==0) {
            $sth = $db->prepare('INSERT INTO `clicktrax`.`targets` (`id`, `stamp_created`, `code`, `url`, `owner_id`) VALUES (NULL, CURRENT_TIMESTAMP, :code, :url, :id)');
            $sth->bindParam(':code', $code, PDO::PARAM_STR);
            $sth->bindParam(':url', $url, PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            $response->withStatus(200);
            $body->write('{"Success":{"msg":"Added target","target_id":'.strval($db->lastInsertId()).'}}');
          }else{
            $response->withStatus(400);
            $body->write('{"Failure":{"msg":"Target code already exists. Please generate an other one"}}');
          }

          $db = null;
      } catch (PDOException $e) {
          $response->withStatus(503);
          $body->write('{"error":{"msg":'.$e->getMessage().'}}');
      }
    }else{
        $response->withStatus(400);
        $body->write('{"Failure":{"msg":"code AND / OR url is not properly formated."}}');
    }
}
);

// Authentication required. Test code availability. returns 'True' or 'False'
// Method : GET
// Parameter 1 : targets.code (String != '' and not null max 32 char)
// (200) => Ok
// (503) => PDOException
// (400) => Failure. code AND / OR url is not properly formated.
// 'Content-type'='application/json'
$app->get('/testCode/{code}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $code = $args['code'];
    if(strlen($code)<=32 and strlen($code)>0 ){
      try {
          $db = getDB();
          $sth = $db->prepare('select * from targets where code= :code');
          $sth->bindParam(':code', $code, PDO::PARAM_STR);
          $sth->execute();
          $codes= $sth->fetchAll(PDO::FETCH_ASSOC);
          if (count($codes)==0) {
            $response->withStatus(200);
            $body->write('{"Result":{"Available":"True"}}');
          }else{
            $response->withStatus(200);
            $body->write('{"Result":{"Available":"False"}}');
          }

          $db = null;
      } catch (PDOException $e) {
          $response->withStatus(503);
          $body->write('{"error":{"msg":'.$e->getMessage().'}}');
      }
    }else{
        $response->withStatus(400);
        $body->write('{"Failure":{"msg":"code AND / OR url is not properly formated."}}');
    }
}
);

// No Authentication required. Do a redirection according to the provided code.
// Keeps track of the HTTP_REFERER header if exists. Stores the hit (DateTime, IP and HTTP_REFERER)
// HTTP_REFERER is transfered to the targeted url.
// Method : GET
// Parameter : targets.code (String != '' and not null)
//  (404) Code note found or code invalid (custom 404 html page)
//  (503) PDOException
//  (301) transfert OK
// 'Content-type'='text/html'
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
        $response->withStatus(503);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
});
