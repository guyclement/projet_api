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
        return is_jwt_valid($bearer_token);
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
                if (get_role() == "guest"){
                    $sql = 'select auteur, datePublication, contenu from article a';
                    $stmt = $linkpdo->prepare($sql);
                    $stmt->execute();
                    $array_complete = array();
                    $i = 0;
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $array_part = array();
                        $array_part["auteur"] = $row["auteur"]; 
                        $array_part["contenu"] = $row["contenu"];
                        $array_part["datePublication"] = $row["datePublication"];
                        $array_complete[$i] = $array_part;
                        $i +=1;
                    }
                    deliver_response(201, "Voici la liste des articles ", $array_complete);
                }else if(in_array("op", $keys)){
                    switch($data["op"]){
                        case "mine" :
                                $sql = 'select * from article a where auteur ="'.get_user().'"';
                                $stmt = $linkpdo->prepare($sql);
                                $stmt->execute();
                                $array_complete = array();
                                $i = 0;
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                                {
                                    $array_part = array();
                                    $array_part["id_article"] = $row["id_article"];
                                    $array_part["auteur"] = $row["auteur"];
                                    $array_part["contenu"] = $row["contenu"];
                                    $array_part["datePublication"] = $row["datePublication"];
                                    $array_part["nombreLike"] = get_count_like_dislike($row["id_article"], 1, $linkpdo);
                                    $array_part["nombreDislike"] =  get_count_like_dislike($row["id_article"], 0, $linkpdo);
                                    $array_complete[$i] = $array_part;
                                    $i +=1;
                                }
                                deliver_response(200, "Voici la liste des articles", $array_complete);
                            break;
                        default:
                            $sql = 'select * from article a';
                            $stmt = $linkpdo->prepare($sql);
                            $stmt->execute();
                            $array_complete = array();
                            $i = 0;
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                            {
                                $array_part = array();
                                $array_part["id_article"] = $row["id_article"];
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
                            deliver_response(200, "Voici la liste des articles", $array_complete);
                            break;
                    }
                }else {
                    deliver_response(401, "Il manque des éléments dans la requête", NULL);
                } 
            }
            break;
        /// Cas de la méthode POST
        case "POST" :
            if (!test_token()){
                deliver_response(403, "token non valide", null);
            }else {
                if (get_role() == "publisher"){
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    $keys = array_keys($data);
                    if(in_array("op", $keys)){
                        switch($data["op"]){
                            case "add" :
                                if (in_array("contenu", $keys)){
                                    $sql = 'insert into article (contenu, datePublication, auteur) values ("'.$data["contenu"].'", now(),"'.get_user().'")';
                                    $stmt = $linkpdo->prepare($sql);
                                    $stmt->execute();
                                    deliver_response(201, "Article bien enregistré", NULL);
                                }else{
                                    deliver_response(401, "Il manque des éléments dans la requête", NULL);
                                } 
                                break;
                            case "like" :
                                if(in_array("statut", $keys) && in_array("id_article", $keys)){
                                    if ($data['statut'] == 1 || $data['statut'] == 0){
                                        //vérifier si il y a déjà un like/dislike
                                        $sql = 'select statut from liker where id_article = '.$data['id_article'].' and pseudo = "'.get_user().'"';
                                        $stmt = $linkpdo->prepare($sql);
                                        $stmt->execute();
                                        if ($verif = $stmt->fetch()) {//vérifie si l'artcle nous appartient
                                            //modifier
                                            $sql2 = 'update liker set statut = '.$data["statut"].' where pseudo = "'.get_user().'" and id_article = '.$data['id_article'];
                                            $stmt2 = $linkpdo->prepare($sql2);
                                            $stmt2->execute();
                                            deliver_response(201, "like bien enregistré", NULL);
                                        }else {
                                            //inserer
                                            $sql2 = 'insert into liker values ('.$data['id_article'].', "'.get_user().'", '.$data["statut"].')';
                                            $stmt2 = $linkpdo->prepare($sql2);
                                            $stmt2->execute();
                                            deliver_response(201, "like bien enregistré", NULL);
                                        } 
                                    }else{
                                        deliver_response(401, "statut doit être égal à 0 ou 1", NULL);
                                    }
                                }else {
                                    deliver_response(401, "Il manque des éléments dans la requête", NULL);
                                } 
                                break;
                        }
                    }
                }else{
                    #TODO
                }
            }
            break;
        /// Cas de la méthode PUT
        case "PUT" :
            if (!test_token()){
                deliver_response(403, "token non valide", null);
            }else {
                if (get_role() == "publisher"){
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    /// Traitement
                    $keys = array_keys($data);
                    if (in_array("id_article", $keys) && in_array("contenu", $keys)){
                        $sql = 'select * from article where id_article = '.$data['id_article'].' and auteur = "'.get_user().'"';
                        $stmt = $linkpdo->prepare($sql);
                        $stmt->execute();
                        if ($verif = $stmt->fetch()) {//vérifie si l'artcle nous appartient
                            $sql2 = 'update article set contenu = "'.$data['contenu'].'" where id_article = '.$data['id_article'].' and auteur = "'.get_user().'"';
                            $stmt2 = $linkpdo->prepare($sql2);
                            $stmt2->execute();
                            deliver_response(201, "Article bien modifié", NULL);
                        }else{
                            deliver_response(403, "Article manquant ou ne vous appartenant pas", NULL);
                        }
                    }else {
                        deliver_response(401, "Il manque des éléments dans la requête", NULL);
                    }
                }
            }
            break;
        /// Cas de la méthode DELETE
        case "DELETE": 
            if (!test_token()){
                deliver_response(403, "token non valide", null);
            }else {
                if (get_role() == "moderator"){
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    /// Traitement
                    $keys = array_keys($data);
                    if (in_array("id_article", $keys)){
                        $sql = 'delete from article where id_article = '.$data['id_article'];
                        $stmt = $linkpdo->prepare($sql);
                        $stmt->execute();
                        deliver_response(201, "Article bien supprimé", NULL);
                    }else {
                        deliver_response(401, "Il manque des éléments dans la requête", NULL);
                    }
                }else if (get_role() == "publisher"){
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    $keys = array_keys($data);
                    if (in_array("id_article", $keys)){
                        $sql = 'select * from article where id_article = '.$data['id_article'].' and auteur = "'.get_user().'"';
                        $stmt = $linkpdo->prepare($sql);
                        $stmt->execute();
                        if ($verif = $stmt->fetch()) {
                            $sql2 = 'delete from article where id_article = '.$data['id_article'].' and auteur = "'.get_user().'"';
                            $stmt2 = $linkpdo->prepare($sql2);
                            $stmt2->execute();
                            deliver_response(201, "Article bien supprimé", NULL);
                        } else {
                            deliver_response(403, "Article inexistant ou ne vous appartenant pas", NULL);
                        }
                    }else {
                        deliver_response(401, "Il manque des éléments dans la requête", NULL);
                    }
                }
            }
            break;
        default :
            deliver_response(404, "Method not find", NULL);
            break;
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