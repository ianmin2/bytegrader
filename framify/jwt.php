<?php



//@ LOAD THE JWT HELPER 
use Ahc\Jwt\JWT;
use Ahc\Jwt\JWTException;


class AuthTokenHandler
{

    private $secret_key;
    private $jwt;
    private $issuer;



    public function __construct($secretkey, $algo = "HS256", int $maxAge = 60 * 60 * 24 * 30)
    {


        if ($secretkey == null) die($this->makeResponse(500, "Failed to capture the current session validation key"));

        $this->secret_key = $secretkey;

        $this->issuer =  ($_SERVER["SERVER_NAME"] != "_") ? $_SERVER["SERVER_NAME"] : $_SERVER["SERVER_ADDR"];

        try {
            $this->jwt = new JWT($this->secret_key, $algo, $maxAge);
        } catch (JWTException $err) {
            echo $err->getMessage();
        }
    }

    private function setEssentials(array $props)
    {
        $props["iss"] = $this->issuer;
        $props["service"] = "ByteGrader";
        return $props;
    }


    public function encode(array $properties)
    {
        if ($properties == null) die($this->makeResponse(500, "Failed to generate a null access token"));
        try {
            $properties = $this->setEssentials($properties);
            return $this->makeResponse(200, $this->jwt->encode($properties));
        } catch (Exception $err) {
            return $this->makeResponse(500, $err->getMessage());
        }
    }

    public function decode($token)
    {
        if ($token == null) die($this->makeResponse(500, "Failed to validate a null access token"));
        try {
            return $this->makeResponse(200, $this->jwt->decode($token));
        } catch (Exception $err) {
            return $this->makeResponse(500, $err->getMessage());
        }
    }


    /** 
     * THE API STYLE JSON [optional] RESPONSE FORMULATOR 
     * */
    private final function makeResponse($response = '', $message = '', $command = '', $encode = true)
    {

        if ($encode) :

            /*
	         * JSON encode and return the response
	         * */

            return /*json_encode*/ (array("response" => strtoupper($response), "data" => array("message" => $message, "command" => $command)));


        else :

            /*
        	 * return the response as is
        	 * */
            return array("response" => strtoupper($response), "data" => array("message" => $message, "command" => $command));


        endif;
    }
}
