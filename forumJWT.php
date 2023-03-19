<?php

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
        /// Cas de la méthode POST
        case "POST" :
            /// Récupération des données envoyées par le Client
            $postedData = file_get_contents('php://input');
            $data = json_decode($postedData, true);
            /// Traitement
            $keys = array_keys($data);
            if (in_array("pseudo", $keys) && in_array("motdepasse", $keys)){
                $login =  $data["pseudo"];
                $mdp =  $data["motdepasse"];

                $hashedMDP = hash("sha256", $mdp, false);
                $sql = 'select * from utilisateur where pseudo = "'.$login.'" and mdp = "'.$hashedMDP.'"';
                $stmt = $linkpdo->prepare($sql);
                $stmt->execute();
                $results = $stmt->fetch(PDO::FETCH_ASSOC);
                $role = $results["role"];
                if (!empty($results)){
                    $header = array("alg" => "HS256", "typ" => "JWT");
                    $payloab = array("username" => $login, "role" => $role, "exp" => (time() + 60*60));
                    deliver_response(201, "login success", generate_jwt($header, $payloab));
                }else {
                    deliver_response(403, "mauvais identifiant", null);
                }
                
            }else {
                $header = array("alg" => "HS256", "typ" => "JWT");
                $payloab = array("username" => "guest", "role" => "guest", "exp" => (time() + 60*60));
                deliver_response(401 , "login as a guest", generate_jwt($header, $payloab));
            }
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