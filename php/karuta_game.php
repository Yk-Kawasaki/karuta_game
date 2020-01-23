<?php
    $name = $_POST["name"];
    $time = time();
    $gLevel = $_POST["gLevel"];
    $score = $_POST["score"];
    function find_name($prev_data,$obj){
        $index = 0;
        $new_data = $prev_data;
        foreach($prev_data->data as $user){
            if(strcmp($user->name, $obj->name) == 0)
            {
                if($user->score <= $obj->score){//前のスコアより低い
                    $new_data = null;
                    break;
                }
                if($user->score > $obj->score){//前のスコアより高い
                    array_splice($new_data->data, $index,1);
                    break;
                }
            }
            ++$index;
        }
        return $new_data;
    }
    function sort_ranking($prev_data,$obj){
        $new_data = find_name($prev_data,$obj);
        $flg = 0;
        if($new_data != null){
            for( $i = 0; $i < count($new_data->data) ; $i++){
                if( $new_data->data[$i]->score > $obj->score){
                    $inserted[] = $obj;
                    array_splice($new_data->data, $i, 0, $inserted);
                    $flg = 1;
                    break;
                }
            }
            if($flg==0)$new_data->data[]=$obj;
            return $new_data;
        }
        return $prev_data;
    }

    if($gLevel > 1){
        $obj = (object) NULL;
        $obj->name = $name;
        $obj->score = $score;
        $obj->date = $time;

        $url = "ranking_bachelor.json";
        switch ($gLevel) {
            case 3:
                $url = "ranking_master.json";
                break;
            case 4:
                $url = "ranking_doctor.json";
                break;
        }
        $file = file_get_contents($url);

        $decoded_json = json_decode($file);

        $newdata = (object) NULL;
        // ランキングが存在する場合、既存のランキングと比較
        if (count($decoded_json->data) > 0) {
            $newdata = sort_ranking($decoded_json,$obj);
        } else {
            $inserted[] = $obj;
            array_splice($decoded_json->data, 0, 0, $inserted);
            $newdata = $decoded_json;
        }
        
        $result = file_put_contents($url, json_encode($newdata));
    }

    $data_url = "data.json";
    $data_file = file_get_contents($data_url);
    $data_json = json_encode($data_file);
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="robots" content="noindex" />
        <title>かるたゲーム</title>
    </head>
    <body>
        <table>
            <tr>
                <td>難易度:</td>
                <td>
                    <input type="radio" name="level" onclick="chooeLevsel(0)">初級　
                    <input type="radio" name="level" onclick="chooeLevsel(1)">中級　
                    <input type="radio" name="level" onclick="chooeLevsel(2)">上級
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="radio" name="level" onclick="chooeLevsel(3)">マスターコース　
                    <input type="radio" name="level" onclick="chooeLevsel(4)">ドクターコース
                </td>
            </tr>
        </table>
        経過時間: <span id="keika">0</span>秒 <span id="penaru"></span> |
        連続正解数: <span id="seikai">0</span> |
        おてつき回数: <span id="otetuki">0</span><br/>
        読み札: <span id="yomifuda" style="background: yellow"></span><br/>
        <span id="jouhou"></span><br/>
        <div id = "yomifudaTable"></div>
        <div id="end">
            <form name="endForm">
                <input type="hidden" id ="endname" name="name" size="40">
                <input type="hidden" id= "endscore" name="score" value="">
                <input type="hidden" id ="endgLevel" name="gLevel" value="0">
            </form>
        </div>
        <script>
            const ONE_SIDE = 5;  //一辺の長さ　デバッグ用
            var gameLevel = (<?php echo json_encode($gLevel); ?> != null ? <?php echo json_encode($gLevel); ?> : 0);
            var isGameStarted;
            var data = JSON.parse(<?php echo $data_json; ?>);
            var torifudaArray = [];
            for (let index = 0; index < ONE_SIDE * ONE_SIDE; index++) {
                torifudaArray.push(index);
            }
            var grkNumArr = [];
            for (let index = 0; index < data.length; index++) {
                grkNumArr.push(index);
            }
            var nowFuda;
            var yomifudaArray = [];
            var itvid;
            var score;
            var continuCorrect;
            var extraScr;
            var isNoMiss;
            var informationTimer;
            var numMiss;
            var readingFudaTimer;
            var nowReading = 0;
            var fullString = "";

            let str = "<table>";
            for (let index = 0; index < ONE_SIDE; index++) {
                str += "<tr>";
                for (let index = 0; index < ONE_SIDE; index++) {
                    str += "<td><button style=\"width: 9.4em;height: 5em;\" class=\"yomi\"></button></td>";
                }
                str += "</tr>";
            }
            str += "</table>";
            document.getElementById("yomifudaTable").innerHTML = str;
            myReset();
            for (let index = 0; index < ONE_SIDE * ONE_SIDE; index++) {
                document.getElementsByClassName("yomi")[index].addEventListener('click', function () {
                    processButton(index);
                });
            }
            document.getElementsByName("level")[gameLevel].checked = true;

            function myReset() {
                isGameStarted = false;
                for (let index = 0; index < ONE_SIDE * ONE_SIDE; index++) {
                    document.getElementsByClassName("yomi")[index].innerHTML = "ゲーム<br/>スタート！";
                }
            }

            function processButton(buttonID) {
                if (!isGameStarted) {
                    isGameStarted = true;
                    yomifudaArray = torifudaArray.slice();
                    for (let index = 0; index < grkNumArr.length; index++) {
                        let randomNum = Math.floor(Math.random() * yomifudaArray.length);
                        let tmp = grkNumArr[index];
                        grkNumArr[index] = grkNumArr[randomNum];
                        grkNumArr[randomNum] = tmp;
                    }
                    placeTorifuda();
                    readFuda();
                    continuCorrect = 0;
                    document.getElementById("seikai").innerHTML = continuCorrect;
                    extraScr = 0;
                    showExtrScr();
                    score = 0;
                    document.getElementById("keika").innerHTML = score;
                    isNoMiss = true;
                    document.getElementById("jouhou").innerHTML = "";
                    numMiss = 0;
                    document.getElementById("otetuki").innerHTML = numMiss;
                    itvid = setInterval(function () {score++;document.getElementById("keika").innerHTML = score;}, 1000);
                } else {
                    if (torifudaArray[buttonID] == nowFuda) {
                        continuCorrect++;
                        document.getElementById("seikai").innerHTML = continuCorrect;
                        nowReading = 0;
                        clearInterval(readingFudaTimer);
                        if (continuCorrect % 10 == 0) {
                            clearTimeout(informationTimer);
                            document.getElementById("jouhou").innerHTML = continuCorrect + "回連続正解！－" + continuCorrect / 10 + "秒";
                            setInfoTimer(3);
                            extraScr -= continuCorrect / 10;
                            showExtrScr();
                        } else {
                            clearTimeout(informationTimer);
                            document.getElementById("jouhou").innerHTML = "正解！";
                            setInfoTimer(1);
                        }
                        if (gameLevel < 2) {
                            document.getElementsByClassName("yomi")[buttonID].disabled = true;
                        }
                        if (yomifudaArray.length == 1) {
                            clearInterval(itvid);
                            if (isNoMiss) {
                                extraScr -= 2;
                                showExtrScr();
                                clearTimeout(informationTimer);
                                document.getElementById("jouhou").innerHTML = "ノーミスクリア！－2秒";
                            }
                            document.getElementsByClassName("yomi")[buttonID].disabled = true;
                            informationTimer = setTimeout(function () {
                                if (gameLevel >= 2) {
                                    let name = prompt("クリア！ 最終スコアは" + (score + extraScr) + "秒\n"
                                                    + "ランキングに登録する場合は名前を入力してOKを押してください\n"
                                                    + "ランキングに登録しない場合はキャンセルを押してください");
                                    if (name != null && name != "") {
                                        end(name);
                                    }
                                } else {
                                    alert("クリア！ 最終スコアは" + (score + extraScr) + "秒");
                                }
                                for (let index = 0; index < ONE_SIDE * ONE_SIDE; index++) {
                                    document.getElementsByClassName("yomi")[index].disabled = false;
                                }
                                document.getElementById("yomifuda").innerHTML = "";
                                myReset();
                            }, 300);
                        } else {
                            yomifudaArray.splice(yomifudaArray.indexOf(torifudaArray[buttonID]), 1);
                            if (gameLevel >= 2) {
                                for (let index = 0; index < ONE_SIDE * ONE_SIDE; index++) {
                                    document.getElementsByClassName("yomi")[index].disabled = false;
                                }
                                placeTorifuda();
                            }
                            readFuda();
                        }
                    } else {
                        isNoMiss = false;
                        numMiss++;
                        document.getElementById("otetuki").innerHTML = numMiss;
                        continuCorrect = 0;
                        document.getElementById("seikai").innerHTML = continuCorrect;
                        if (numMiss % 2 == 0) {
                            extraScr ++;
                            showExtrScr();
                            clearTimeout(informationTimer);
                            document.getElementById("jouhou").innerHTML = numMiss + "回目のおてつき... ＋1秒";
                            setInfoTimer(3);
                        } else {
                            clearTimeout(informationTimer);
                            document.getElementById("jouhou").innerHTML = "おてつき...";
                            setInfoTimer(1);
                        }
                    }
                }
            }

            function setInfoTimer(seconds) {
                informationTimer = setTimeout(function () {document.getElementById("jouhou").innerHTML = "";}, seconds * 1000);
            }
            
            function showExtrScr() {
                if (extraScr == 0) {
                    document.getElementById("penaru").innerHTML = "";
                } else {
                    document.getElementById("penaru").innerHTML = (extraScr > 0 ? "+" : "") + extraScr + "秒";
                }
            }

            function placeTorifuda() {
                for (let index = 0; index < torifudaArray.length; index++) {
                    let randomNum = Math.floor(Math.random() * yomifudaArray.length);
                    let tmp = torifudaArray[index];
                    torifudaArray[index] = torifudaArray[randomNum];
                    torifudaArray[randomNum] = tmp;
                }
                for (let index = 0; index < torifudaArray.length; index++) {
                    document.getElementsByClassName("yomi")[index].innerHTML = (
                        gameLevel < 4
                        ? data[grkNumArr[torifudaArray[index]]].shimo
                        : data[grkNumArr[torifudaArray[index]]].shimo.split(" ")[1]);
                    if (yomifudaArray.indexOf(torifudaArray[index]) == -1) {
                        document.getElementsByClassName("yomi")[index].disabled = true;
                    }
                }
            }

            function readFuda() {
                let randomNum = Math.floor(Math.random() * yomifudaArray.length);
                nowFuda = yomifudaArray[randomNum];
                if (gameLevel < 3) {
                    document.getElementById("yomifuda").innerHTML
                        = data[grkNumArr[nowFuda]].kami + (gameLevel == 0 ? " " + data[grkNumArr[nowFuda]].shimo : "");
                } else {
                    fullString = data[grkNumArr[nowFuda]].kami + " " +data[grkNumArr[nowFuda]].shimo;
                    nowReading = data[grkNumArr[nowFuda]].kimariLength;
                    document.getElementById("yomifuda").innerHTML
                        = fullString.substr(0, nowReading);
                    readingFudaTimer = setInterval(function () {
                            if (nowReading >= fullString.length) {
                                clearInterval(readingFudaTimer);
                            } else {
                                nowReading++;
                                document.getElementById("yomifuda").innerHTML
                                    = fullString.substr(0, nowReading);
                            }
                        }, 500);
                }
            }
            
            function chooeLevsel(chedLevel) {
                if (isGameStarted) {
                    document.getElementsByName("level")[gameLevel].checked = true;
                } else {
                    gameLevel = chedLevel;
                }
            }

            function end(name){
                document.getElementById("endname").value = name;
                document.getElementById("endscore").value = score + extraScr;
                document.getElementById("endgLevel").value = gameLevel;
                document.endForm.method = "post";
                document.endForm.submit();
            }
        </script>
    </body>
</html>
