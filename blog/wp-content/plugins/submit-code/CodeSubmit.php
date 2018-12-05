<?php
/**
 * Plugin Name:       Submit Code
 * Description:       Submit your code (Tester)
 * Version:           1.0.0
 * Author:            IndieTeam
 * Author URI:
 * Text Domain:
 * License:
 * License URI:
 * GitHub Plugin URI:
 */

/*
 * Plugin constants
 */
if(!defined('CODE_SUBMIT_URL'))
    define('FEEDIER_URL', plugin_dir_url( __FILE__ ));
if(!defined('CODE_SUBMIT_PATH'))
    define('FEEDIER_PATH', plugin_dir_path( __FILE__ ));

add_filter( 'the_content', function ($content){
    // init post, length
    if (is_single()) {
        $pos_start = 0;
        $pos_end = mb_strpos($content, 'start-test');
        $pos_last =  mb_strpos($content, 'end-test');
        $content_length = strlen($content);

        if ($pos_end == false || $pos_last == false){
            return $content;
        }

        // test case string
        $test_case = mb_substr($content, $pos_end + strlen('start-test'), $content_length - strlen(' end-test'));
        echo $test_case;
        $test_case = str_split($test_case);
        $line = (string)'';
        $test_case_array = [];
        foreach ($test_case as $char) {
            if ($char != ';') {
                $line .= (string)$char;
            }
            if ($char == ';') {
                $line .= ';';
                $line = trim($line);
                $pos_input = mb_strpos($line, 'input:');
                $pos_output = mb_strpos($line, 'output:');
                $input = mb_substr($line, $pos_input + strlen('input:') + 1, $pos_output - strlen('output:') - 1);
                $output = mb_substr($line, $pos_output + strlen('output:') + 1, strlen($line) - strlen($input) - strlen('output:') - strlen('input:') - 5);
                if ($input != '' && $output != '') {
                    echo '<br>' . $input . ' : ' . $output;
                    //echo '<br>' . $line;
                    $test_case_array[] = new TestCase($input, $output);
                }
                $line = '';
            }
        }
        // content string without test caseget_site_urlz
        $content = mb_substr($content, $pos_start, $pos_end);
        echo '<br>';
        echo '
               <link rel="stylesheet" href="'.get_site_url().'/wp-content/plugins/submit-code/assets/code-editor/theme/material.css">
               <link rel="stylesheet" href="'.get_site_url().'/wp-content/plugins/submit-code/assets/code-editor/lib/codemirror.css">
               <script src="'.get_site_url().'/wp-content/plugins/submit-code/assets/code-editor/lib/codemirror.js"></script>
               <script src="'.get_site_url().'/wp-content/plugins/submit-code/assets/code-editor/mode/javascript/javascript.js"></script>
               <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
            ';
        echo $content;
        if (is_user_logged_in()) {
            echo '<br>';
            echo '<style>
                .accepted{
                    font-weight: bold;
                    color: green;
                    height: 0px;
                }
                .wrong{
                    font-weight: bold;
                    color: red;
                    height: 0px;
                }
                .CodeMirror{
                border: 3px solid #263238;
                    border-radius: 10px;
                }
                .submit-code-btn{
                    background: #263238;
                    color: white;
                    width: 80px;
                    height: 40px;
                    text-align: center;
                    border-radius: 10px;
                    margin-top: 20px;
                }
                .submit-code-btn:
              </style>';
            echo '<textarea id="code-editor" name="source" required></textarea>';
            echo '<button onclick="submit_code()" class="submit-code-btn">Submit</button>';
            echo '<p></p>';
            echo '<div class="submit-result">
                   </div>';
            echo '<script>
                    var clicked = 0;
                    var input = new Array();
                    var output = new Array();
                    </script>';
            foreach ($test_case_array as $value){
                echo '<script> input.push("'.$value->input.'") </script>';
                echo '<script> output.push("'.$value->output.'") </script>';
            }
            echo '<script>
                    var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("code-editor"), {
                                            lineNumbers: true,
                                             theme: "material"
                                          });
                    async function submit_code() {
                        var source_code = myCodeMirror.getValue()
                        if (source_code != "")
                            clicked++;
                        var count_unit_test = 1;
                        var total = input.length;
                        var pass = 0;
                        document.getElementsByClassName("submit-code-btn")[0].style.color = "white";
                        if (clicked === 1) {
                            await $( ".submit-result" ).empty();
                            if (source_code != ""){ 
                                for (var i=0; i< input.length; i++){
                                    await $.ajax({
                                              method: "POST",
                                              url: "' . get_site_url() . '/wp-content/plugins/submit-code/api.php",
                                              data: { 
                                                  source: source_code,
                                                  stdin: input[i],
                                                  expected_output:  output[i]
                                               }
                                            })
                                          .done(async function(data) {
                                              var json = JSON.stringify(data);
                                              var dataJson = JSON.parse(json);
                                              //console.log(dataJson);
                                              console.log(dataJson.status.description);
                                              console.log("Expected output" + output[i]);
                                              console.log("Your output: ",  atob(dataJson.stdout));
                                              if (dataJson.status.description === "Accepted") {
                                                  pass++;
                                                  await $(".submit-result").append("<p class=accepted>"+count_unit_test+". Accepted</p>"); 
                                              } else { 
                                                  await $(".submit-result").append("<p class=wrong>"+count_unit_test+". Wrong</p>"); 
                                              }
                                          })
                                          .fail(function(jqXHR, textStatus, errorThrown) {
                                              alert( errorThrown );
                                          });
                                    count_unit_test++;
                                    //console.log("input:" + String.raw`${input[i]}` + "output:" + String.raw`${output[i]}`+";")
                                } 
                                await $(".submit-result").append("<br><br>");
                                if (pass < total/2) 
                                    await $(".submit-result").append("<h4 class=Wrong> Passed: "+pass+"/"+total+"</h4>");
                                else 
                                    await $(".submit-result").append("<h4 class=accepted> Passed: "+pass+"/"+total+"</h4>");
                                clicked = 0;
                            }
                        }
                    }
              </script>';
        }
        return '';
    }
}, 0);


class TestCase{
    public $input;
    public  $output;

    function __construct($input, $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}