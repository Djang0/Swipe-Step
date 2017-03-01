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
// Authentication required. Returns all hooks data having an id > {id} param.
// Method : GET
// (200) => Ok
// (503) => PDOException
// 'Content-type'='application/json'
$app->get('/getHooks/{name}/{id}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $date = date_create();
    $ref_id = intval($args['id']);
    $hook_name = $args['name'];
    if(strlen($hook_name)==32){
      try {
        $db = $this->db;
        $sth = $db->prepare('select * from hooks where name= :name');
        $sth->bindParam(':name', $hook_name, PDO::PARAM_STR);
        $sth->execute();
        $hooks = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (count($hooks) == 1) {
            $hook_id=$hooks[0]['id'];
            $sth = $db->prepare('SELECT hook_calls.call_details  FROM hook_calls WHERE hook_calls.id > :ref_id and hook_id = :hook_id');
            $sth->bindParam(':ref_id', $ref_id, PDO::PARAM_INT);
            $sth->bindParam(':hook_id', $hook_id, PDO::PARAM_INT);
            $sth->execute();
            $calls = $sth->fetchAll(PDO::FETCH_ASSOC);
            $data['result'] = array(
              'timestamp' => date_format($date, 'd-m-Y H:i:s'),
              'hook_call_count' => count($calls),
              'hook_calls' => $calls,
          );
            $response = $response->withStatus(200);
            $body->write(json_encode($data));
        } else {
          $response->withStatus(422);
          $body->write('{"Failure":{"msg":"Hook does not exists."}}');
        }

        $db = null;

      } catch (PDOException $e) {
          $response->withStatus(503);
          $body->write('{"error":{"msg":'.$e->getMessage().'}}');
      }
    }else{
      $response->withStatus(400);
      $body->write('{"Failure":{"msg":"Not a hook name."}}');
    }

});
// Authentication required. Returns all hooks.
// Method : GET
// (200) => Ok
// (503) => PDOException
// 'Content-type'='application/json'
$app->get('/getHooks/', function ($request, $response, $args) {
    $id = $response->getHeaderLine('X-Owner');
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $data = array();
    $date = date_create();
    try {
        $db = $this->db;
        $sth = $db->prepare('SELECT *  FROM hooks where hooks.owner_id = :owner_id');
        $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
        $sth->execute();
        $hooks = $sth->fetchAll(PDO::FETCH_ASSOC);
        $data['result'] = array(
          'timestamp' => date_format($date, 'd-m-Y H:i:s'),
          'hooks_count' => count($hooks),
          'hooks' => $hooks,
      );
        $response = $response->withStatus(200);
        $body->write(json_encode($data));
        $db = null;
    } catch (PDOException $e) {
        $response->withStatus(503);
        $body->write('{"error":{"msg":'.$e->getMessage().'}}');
    }
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
        // $db = getDB();
        $db = $this->db;
        $sth = $db->prepare('SELECT targets.id, targets.stamp_created, targets.url, targets.code  FROM targets WHERE targets.owner_id = :owner_id');
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
            // $db = getDB();
            $db = $this->db;
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
            // $db = getDB();
            $db = $this->db;
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

// Authentication required. creates a Hook by generating MD5 of the string_to_encode parameter.
// Method : GET
// Parameter 1 : string_to_encode (String != '')
// (200) => Ok
// (503) => PDOException
// (400) => Failure. name is not properly formated. | hook already exists.
// 'Content-type'='application/json'
$app->get('/addHook/{string_to_encode}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $id = $response->getHeaderLine('X-Owner');
    $code = strtolower($args['string_to_encode']);
    if (strlen($code) > 0) {
      $md5 = md5($code.$id);
        try {
            // $db = getDB();
            $db = $this->db;
            $sth = $db->prepare('SELECT * from hooks where hooks.name = :md5 and hooks.owner_id = :owner_id');
            $sth->bindParam(':md5', $md5, PDO::PARAM_STR);
            $sth->execute();
            $hooks = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (count($hooks) == 0) {
                $sth = $db->prepare('INSERT INTO hooks (name, owner_id) VALUES (:md5 :owner_id)');
                $sth->bindParam(':md5', $md5, PDO::PARAM_STR);
                $sth->bindParam(':owner_id', $id, PDO::PARAM_INT);
                $sth->execute();
                $response->withStatus(200);
                $body->write('{"Success":{"msg":"Created hook","hook_name":"'.$md5.'"}}');
            } else {
                $response->withStatus(400);
                $body->write('{"Failure":{"msg":"Hook code already exists. Please generate an other one"}}');
            }

            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
    } else {
        $response->withStatus(400);
        $body->write('{"Failure":{"msg":"name is not properly formated."}}');
    }
}
);


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
    $code = strtolower($args['code']);
    $url = $args['url'];
    if (strlen($code) <= 32 and strlen($code) > 0 and strlen($url) <= 2083 and strlen($url) > 0) {
        try {
            // $db = getDB();
        $db = $this->db;
            $sth = $db->prepare('select * from targets where code= :code');
            $sth->bindParam(':code', $code, PDO::PARAM_STR);
            $sth->execute();
            $codes = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (count($codes) == 0) {
                $sth = $db->prepare('INSERT INTO `clicktrax`.`targets` (`id`, `stamp_created`, `code`, `url`, `owner_id`) VALUES (NULL, CURRENT_TIMESTAMP, :code, :url, :id)');
                $sth->bindParam(':code', $code, PDO::PARAM_STR);
                $sth->bindParam(':url', $url, PDO::PARAM_STR);
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->execute();
                $response->withStatus(200);
                $body->write('{"Success":{"msg":"Added target","target_id":'.strval($db->lastInsertId()).'}}');
            } else {
                $response->withStatus(400);
                $body->write('{"Failure":{"msg":"Target code already exists. Please generate an other one"}}');
            }

            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
    } else {
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
    $code = strtolower($args['code']);
    if (strlen($code) <= 32 and strlen($code) > 0) {
        try {
            // $db = getDB();
            $db = $this->db;
            $sth = $db->prepare('select * from targets where code= :code');
            $sth->bindParam(':code', $code, PDO::PARAM_STR);
            $sth->execute();
            $codes = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (count($codes) == 0) {
                $response->withStatus(200);
                $body->write('{"Result":{"Available":"True"}}');
            } else {
                $response->withStatus(200);
                $body->write('{"Result":{"Available":"False"}}');
            }

            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
    } else {
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
        $code = strtolower($args['code']);
        if (!is_null($code) and is_string($code) and $code != '') {
            // $db = getDB();
            $db = $this->db;
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
// No Authentication required. Does log the content of POST data to the hook_calls table.
// Method : POST
//  (503) PDOException
//  (200) Ok !
// 'Content-type'='application / json'
$app->post('/hook/{name}', function ($request, $response, $args) {
    $response = $response->withHeader('Content-type', 'application/json');
    $body = $response->getBody();
    $hook_name = strtolower($args['name']);
    $data = $request->getBody();
    if (strlen($hook_name) == 32 ) {
        try {
            // $db = getDB();
            $db = $this->db;
            $sth = $db->prepare('select * from hooks where name= :name');
            $sth->bindParam(':name', $hook_name, PDO::PARAM_STR);
            $sth->execute();
            $hooks = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (count($hooks) == 1) {
                $id=$hooks[0]['id'];
                $sth = $db->prepare('insert into hook_calls(hook_id,call_details) values(:id,:code)');
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->bindParam(':code', $data, PDO::PARAM_STR);
                $sth->execute();
                $db = null;
                $response = $response->withStatus(200);
                $body->write('{"success":"Ok !"}');
            } else {
              $response->withStatus(422);
              $body->write('{"Failure":{"msg":"Hook does not exists."}}');
            }

            $db = null;
        } catch (PDOException $e) {
            $response->withStatus(503);
            $body->write('{"error":{"msg":'.$e->getMessage().'}}');
        }
    } else {
        $response->withStatus(400);
        $body->write('{"Failure":{"msg":"Not a hook name."}}');
    }
});
