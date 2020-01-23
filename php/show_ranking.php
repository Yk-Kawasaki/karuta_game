<?php
    $gLevel = $_POST["gLevel"];
    $url = "ranking_bachelor.json";
    switch ($gLevel) {
        case 1:
            $url = "ranking_master.json";
            break;
        case 2:
            $url = "ranking_doctor.json";
            break;
    }
    $file = file_get_contents($url);
    $json = json_encode($file);
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="robots" content="noindex" />
        <title>かるたゲームのランキング</title>
    </head>
    <body>
    <div id="scoreBoard">
        <input type="radio" name="level" onclick="sendLevsel(0)">上級　
        <input type="radio" name="level" onclick="sendLevsel(1)">マスターコース　
        <input type="radio" name="level" onclick="sendLevsel(2)">ドクターコース<br/>
        ランキング
        <button onclick="sendLevsel(-1)">更新</button>
    </div>
    <div id="chLv">
        <form name="choseLvForm">
            <input type="hidden" id ="endgLevel" name="gLevel" value="0">
        </form>
    </div>
    <style>
        td,th{
            padding:10px;
            text-align: center;
            }
        th{
            color:#fff;
            background:#005ab3;
        }
            table tr:nth-child(odd){
            background:#e6f2ff;
        }
    </style>

    <script>
        var nowLevel = (<?php echo json_encode($gLevel); ?> != null ? <?php echo json_encode($gLevel); ?> : 0);
        document.getElementsByName("level")[nowLevel].checked = true;
        function sendLevsel(chedlevel) {
            document.getElementById("endgLevel").value = (chedlevel == -1 ? nowLevel : chedlevel);
            document.choseLvForm.method = "post";
            document.choseLvForm.submit();
        }
        function change_timestamp2time(d){
            d = new Date( d * 1000 );
            var year  = d.getFullYear();
            var month = d.getMonth() + 1;
            var day   = d.getDate();
            var hour  = ( d.getHours()   < 10 ) ? '0' + d.getHours()   : d.getHours();
            var min   = ( d.getMinutes() < 10 ) ? '0' + d.getMinutes() : d.getMinutes();
            var sec   = ( d.getSeconds() < 10 ) ? '0' + d.getSeconds() : d.getSeconds();
            return ( year + '年' + month + '月' + day + '日 ' + hour + ':' + min + ':' + sec );
        }
        var ranking = JSON.parse(<?php echo $json; ?>);
        if (ranking['data'].length == 0) {
            var boardMessage = document.createElement('p');
            boardMessage.innerHTML = 'ランキングなし';
            document.getElementById("scoreBoard").appendChild(boardMessage);
        } else {
            var rows=[];
            var table = document.createElement("table");
            rows.push(table.insertRow(-1)); 
            cell=rows[0].insertCell(-1);
            cell.appendChild(document.createTextNode("順位"));
            cell=rows[0].insertCell(-1);
            cell.appendChild(document.createTextNode("名前"));
            cell=rows[0].insertCell(-1);
            cell.appendChild(document.createTextNode("スコア"));
            cell=rows[0].insertCell(-1);
            cell.appendChild(document.createTextNode("日時"));
            for(i = 0; i < ranking['data'].length; i++){
                rows.push(table.insertRow(-1)); 
                cell=rows[i+1].insertCell(-1);
                cell.appendChild(document.createTextNode(i+1+"位:"));
                cell=rows[i+1].insertCell(-1);
                cell.appendChild(document.createTextNode(ranking['data'][i]["name"]));
                cell=rows[i+1].insertCell(-1);
                cell.appendChild(document.createTextNode(ranking['data'][i]["score"]+"秒"));
                cell=rows[i+1].insertCell(-1);
                cell.appendChild(document.createTextNode(change_timestamp2time(ranking['data'][i]["date"])));
            }
            document.getElementById("scoreBoard").appendChild(table);
        }
    </script>
    </body>
</html>