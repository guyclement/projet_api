<?php

    function get_role($secret = 'secret'){
        $jwt = '';
        $jwt = get_bearer_token();
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $payload = base64_decode($tokenParts[1]);

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->role;
        return $expiration;
    }

    function get_user($secret = "secret"){
        $jwt = '';
        $jwt = get_bearer_token();
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $payload = base64_decode($tokenParts[1]);

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->username;
        return $expiration;
    }
    function test_token(){
        $bearer_token = '';
        $bearer_token = get_bearer_token();
        if (is_jwt_valid($bearer_token)){
            return true;
        }else{
            return false;
        }
    }
    function get_liste_like_dislike($idArticle,$statut, $linkpdo){
        $query = 'select pseudo from liker l where statut = '.$statut.' and l.id_article = '.$idArticle;
        $stmt = $linkpdo->prepare($query);
        $stmt->execute();
        $result = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            array_push($result, $row["pseudo"]);
        }
        return $result;
    }

    function get_count_like_dislike($idArticle,$statut, $linkpdo){
        $query = 'select count(*) nb from liker l where statut = '.$statut.' and l.id_article = '.$idArticle;
        $stmt = $linkpdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)["nb"];
    }

    /// Paramétrage de l'entête HTTP (pour la réponse au Client)
    header("Content-Type:application/json; charset=utf-8");
    require_once("jwt_utils.php");
    $login = "root";
    $mdp = "";
    try {
        $linkpdo = new PDO("mysql:host=localhost;port=3306;dbname=projetforum", $login, $mdp);
        $linkpdo->exec("SET CHARACTER SET utf8");
    }
    catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }

    /// Identification du type de méthode HTTP envoyée par le client
    $http_method = $_SERVER['REQUEST_METHOD'];
    switch ($http_method){
        /// Cas de la méthode GET
        case "GET" :
            if (!test_token()){
                deliver_response(403, "token non valide", null);
            }else {
                $postedData = file_get_contents('php://input');
                $data = json_decode($postedData, true);
                $keys = array_keys($data);
                if(in_array("op", $keys)){
                    switch($data["op"]){
                        case "all" :
                            //deliver_response(200, "token valide", null);
                            $sql = 'select * from article a';
                            $stmt = $linkpdo->prepare($sql);
                            $stmt->execute();
                            //$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            //deliver_response(200, "Tout les articles", $results);
                            $array_complete = array();
                            $i = 0;
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                            {
                                $array_part = array();
                                $array_part["auteur"] = $row["auteur"];
                                $array_part["contenu"] = $row["contenu"];
                                $array_part["datePublication"] = $row["datePublication"];
                                if (get_role() == "moderator"){
                                    $array_part["listeLike"]  = get_liste_like_dislike($row["id_article"], 1, $linkpdo);
                                    $array_part["listeDislike"]  = get_liste_like_dislike($row["id_article"], 0, $linkpdo);
                                }
                                $array_part["nombreLike"] = get_count_like_dislike($row["id_article"], 1, $linkpdo);
                                $array_part["nombreDislike"] =  get_count_like_dislike($row["id_article"], 0, $linkpdo);
                                $array_complete[$i] = $array_part;
                                $i +=1;
                            }
                            deliver_response(201, "mon message", $array_complete);
                            deliver_response(200, "role", get_role());
                            break;
                        case "mine" :
                            if (get_role() == "publisher"){
                                $sql = 'select * from article a where auteur ="'.get_user().'"';
                                $stmt = $linkpdo->prepare($sql);
                                $stmt->execute();
                                //$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                //deliver_response(200, "Tout les articles", $results);
                                $array_complete = array();
                                $i = 0;
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                                {
                                    $array_part = array();
                                    $array_part["auteur"] = $row["auteur"];
                                    $array_part["contenu"] = $row["contenu"];
                                    $array_part["datePublication"] = $row["datePublication"];
                                    $array_part["nombreLike"] = get_count_like_dislike($row["id_article"], 1, $linkpdo);
                                    $array_part["nombreDislike"] =  get_count_like_dislike($row["id_article"], 0, $linkpdo);
                                    $array_complete[$i] = $array_part;
                                    $i +=1;
                                }
                                deliver_response(201, "mon message", $array_complete);
                                }
                            break;
                        default:
                            break;
                    }
                }
            }
            break;
        /// Cas de la méthode POST
        case "POST" :
            if (!test_token()){
                deliver_response(403, "token non valide", null);
            }else {
                $postedData = file_get_contents('php://input');
                $data = json_decode($postedData, true);
                /// Traitement
                $keys = array_keys($data);
                if (in_array("contenu", $keys) && in_array("auteur", $keys)){
                    $sql = 'insert into article (contenu, datePublication, auteur) values ("'.$data["contenu"].'", now(),"'.$data["auteur"].'")';
                    $stmt = $linkpdo->prepare($sql);
                    $stmt->execute();
                    deliver_response(201, "message bien enregistré", NULL);
                }else{
                    deliver_response(401, "manque des éléments", NULL);
                }
            }
            break;
        /// Cas de la méthode PUT
        case "PUT" :
            break;
        /// Cas de la méthode DELETE
        default :
        break;
        /*
            /// Récupération de l'identifiant de la ressource envoyé par le Client
            if (!empty($_GET['mon_id'])){
            /// Traitement
                $sql = 'delete from chuckn_facts where id = '.$_GET['id'];
                $stmt = $linkpdo->prepare($sql);
                $stmt->execute();
            }
            /// Envoi de la réponse au Client
            deliver_response(200, $sql, NULL);
            break;
        */
        }
        /// Envoi de la réponse au Client

    function deliver_response($status, $status_message, $data){
        /// Paramétrage de l'entête HTTP, suite
        header("HTTP/1.1 $status $status_message");
        /// Paramétrage de la réponse retournée
        $response['status'] = $status;
        $response['status_message'] = $status_message;
        $response['data'] = $data;
        /// Mapping de la réponse au format JSON
        $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo $json_response;
    }
?>