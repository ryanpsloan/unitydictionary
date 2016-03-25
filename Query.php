<?php

class Query {
    /**
     * class properties
     * @var $date string date and time to log query
     * @var $field string inputted data from the user
     * @var $radio string the value of the selected radio button
     * @var $query string the holds the URL of the API query
     * @var $myFile string
     * @var $baseUrl string
     * @var $logFile string points to the log file for fopen fclose
     * @arr $jsonData string holds the json data returned by API
     * @var $results string value outputted to the user
     * @arr $turkishData string array created to cut out unnecessary data
     * @var $turkish string designates the language option for the API search
     * @var $url string holds the completed URL
     */
    public $date;
    public $field;
    public $radio;
    public $query;
    private $myFile;
    private $baseUrl = "http://cevir.ws/v1";
    private $logFile = "logFile.txt";
    private $jsonData;
    private $results;
    private $turkishData;
    private $turkish;
    private $url;
    //all queries are logged
    public function getLogFile()
    {
        return $this->logFile;
    }
    public function setLogFile($newLogName)
    {
        $this->logFile = $newLogName;
    }
    public function __construct($field, $radio)
    {
        //open log file
        try {
            $this->myFile = fopen($this->logFile, "a");
        }catch(Exception $e){
            echo $e->getMessage(); //"<p>Cannot open file ($this->logFile)</p><br>";
            throw(new Exception("Cannot open file ($this->logFile)",0,$e));
        }
        //get current datetime object
        $this->date = new DateTime("now", new DateTimeZone("America/Denver"));
        //change it to a string
        $this->date = $this->date->format("Y-m-d H:i:s");
        //set the values from the user
        $this->setQuery($field, $radio);
        //submits query to API and gets return of data
        $this->setData();
        //prints the results to the outputDiv
        $this->printResults();
        //logging actions
        $this->detailLog();
        //close file
        $this->closeFile();
    }
    private function closeFile(){
        fclose($this->myFile);
    }
    //takes input from user in the form of a string and radio button selection and constructs
    //the url needed to query the api
    private function setQuery($field, $radio){
        if(empty($field) === false &&
            //turkish uses latin characters but for correct translation they need to be converted to ISO-8859-9
            ($this->field = filter_input(INPUT_GET, "field", FILTER_SANITIZE_STRING)) !== false) {
            $this->field = urlencode($this->field);

            //check the settings of the radio button
            $lang = "";
            if(($this->radio = $radio) === "tr") {
                $this->turkish = true;
                $lang = "tr";
            } else if($this->radio === "en") {
                $this->turkish = false;
                $lang = "en";
            }
            //construct the query
            $this->query = "?q=$this->field&m=25&p=exact&l=$lang";
        }
        // defeat malicious & incompetent users
        if(empty($this->field) === true)
        {
            echo "<p>Nothing was Submitted</p>";
            exit;
        }
    }
    private function createURL(){
        // final URL to submit to Cevir API
        $this->url = "$this->baseUrl$this->query";
        return $this->url;
    }
    private function setData()
    {
        $finalURL = $this->createURL();

        // fetch the raw JSON data /*@ suppressed warnings*/
        $this->jsonData = file_get_contents($finalURL);
        if($this->jsonData === false) {
            echo "<p>Unable to download data from server</p>";
            //exit;
        }
        // convert the JSON data into a big associative array
        $this->jsonData = json_decode($this->jsonData, true);
        if($this->jsonData['control']['results'] > 0) {
            $this->turkishData['control'] = $this->jsonData['control'];
            //trim unnecessary data
            for($i = 0; $i < $this->jsonData['control']['results']; $i++) {
                $this->turkishData['word'][$i] =
                    array("lang"  => $this->jsonData['word'][$i]['lang'],
                        "title" => $this->jsonData['word'][$i]['title'],
                        "desc"  => $this->jsonData['word'][$i]['desc']);
            }
        }
        else {
            echo "<p>Unable to get query, check word is in Turkish or English and that you are setting the appropriate radio button
			for your query</p>";
            //exit;
        }
    }
    private function printResults()
    {
        // echo select fields from the array (cut unnecessary data)
        if($this->turkishData["control"]["status"] === "ok") {
            if($this->turkish === true && $this->turkishData['control']['results'] !== 0) {
                for($i = 0; $i < $this->turkishData["control"]["results"]; $i++) {
                    if($this->turkishData['word'][$i]['lang'] === "tr") {
                        echo "<p>".($i+1)." | " . $this->turkishData["word"][$i]["desc"] . "</p><br>";
                        $this->results[] = $this->turkishData["word"][$i]["desc"];
                    }
                }
            } else if($this->turkish === false && $this->turkishData['control']['results'] !== 0) {
                for($i = 0; $i < $this->turkishData["control"]["results"]; $i++) {
                    if($this->turkishData['word'][$i]['lang'] === "en") {
                        echo "<p>".($i+1)." | " . $this->turkishData["word"][$i]["desc"] . "</p><br>";
                        $this->results[] = $this->turkishData["word"][$i]["desc"];
                    }
                }
            } else {
                echo "<p>No entries for your query were found</p>";
                //exit;
            }
        }
        else {
            echo "<p>no results were returned</p>";
            //exit;
        }
    }
    //logging function
    private function detailLog(){
        try {
            if (is_writable($this->logFile)) {
                fwrite($this->myFile, "Query Date: " . $this->date . "\r\n \r\n");
                fwrite($this->myFile, "\r\n");
                fwrite($this->myFile, "**field** = " . $this->field . "\r\n \r\n");
                fwrite($this->myFile, "\r\n");
                fwrite($this->myFile, "**radio** = " . $this->radio . "\r\n \r\n");
                fwrite($this->myFile, "\r\n");
                fwrite($this->myFile, "**URL** = " . $this->url . "\r\n \r\n");
                fwrite($this->myFile, "**var_dump jsonData** \r\n \r\n" . var_export($this->jsonData, true));
                fwrite($this->myFile, "\r\n");
                fwrite($this->myFile, "**var_dump turkishData** \r\n \r\n" . var_export($this->turkishData, true));
                fwrite($this->myFile, "\r\n");
                for ($i = 0; $i < count($this->results); $i++) {
                    fwrite($this->myFile, $this->results[$i]);
                }
                fwrite($this->myFile, "\r\n \r\n");
            }
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
    //public function to print the log to screen
    public function printLog(){
        if($openFile = fopen($this->logFile, "rb")){
            while (($buffer = fgets($openFile, filesize($this->logFile))) !== false) {
                echo $buffer."<br>";
            }
            if (!feof($openFile)) {
                echo "<p>Error Printing: unable to access file to read</p><br>";
            }
            fclose($openFile);
        }
        else{
            echo "<p>Unable to print $this->logFile, unable to open file, check filename</p><br>";
        }
    }
}
?>