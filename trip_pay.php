<?php
  if(!isset($_POST["raw_data"])){
    echo file_get_contents("trip_pay.html");
    exit();
  }
  $data = $_POST["raw_data"];
  $data = explode(PHP_EOL,$data);
  $total_humans = 0;
  foreach ($data as $part) {
    $part = trim($part);
    if($part == ""){
      continue;
    }
    if($part[0] == "!"){
      continue;
    }
    $total_humans++;
  }
  $names = array();
  $multicast = array();
  $multicast_itterators = array();
  $result = array();
  $i = 0;
  foreach($data as $part){
    $part = trim($part);
    if($part == ""){
      continue;
    }
    if($part[0] == "!"){

      $part = substr($part,1);
      $part = explode(":",$part);
      $value = floatval($part[1]);

      $part = explode("=>",$part[0]);
      $owner = trim($part[1]);
      if(!isset($multicast_itterators[$owner])){
        $multicast_itterators[$owner] = 0;
      }else{
        $multicast_itterators[$owner]++;
      }

      $multicast_humans = explode(",",$part[0]);
      $multicast_return_part = count($value / count($multicast_humans));

      foreach($multicast_humans as $creditor){
        $creditor = trim($creditor);
        $multicast[$owner][$multicast_itterators[$owner]][$creditor] = $multicast_return_part;
      }

      continue;
    }
    $temp = explode(":",$part);
    $names[$i]["name"] = trim($temp[0]);
    $names[$i]["credits"] = floatval(trim($temp[1]));
    $names[$i]["each_return_part"] = round(floatval($temp[1])/$total_humans,2);

    $i++;
  }
  echo "Всього $total_humans людей<br><br>";

  foreach($multicast as $owner => $temp){
    foreach ($temp as $key => $val) {
      $humans_to_pay = "";
      $price = 0;
      foreach($val as $human => $trunk){
        if($human == $owner){
          continue;
        }
        $humans_to_pay .= "$human, ";
        $price = $trunk;
      }
      echo "$owner вніс за: $humans_to_pay по $price грн <br><br>";
    }
  }


  echo "<table cellspacing='0px'>";
  echo "
    <tr>
      <td><b>Хто</b></td>
      <td><b>Скільки вніс</b></td>
      <td><b>По скільки скидатись</b></td>
    </tr>
  ";
  $i = 0;
  foreach($names as $name){
    echo "
      <tr$color>
        <td>{$name["name"]}</td>
        <td>{$name["credits"]} грн.</td>
        <td>{$name["each_return_part"]} грн</td>
      </tr>
    ";
  }

  echo "</table><br><br>";

  for($i = 0; $i < $total_humans; $i++){
    for($j = $i + 1; $j < $total_humans; $j++){

      $first_return_part = $names[$i]["each_return_part"];
      $second_return_part = $names[$j]["each_return_part"];

      $first_val = 0;
      $second_val = 0;

      foreach($multicast[$names[$i]["name"]] as $humans){
        $owner = $names[$i]["name"];
        $creditor = $names[$j]["name"];

        if(isset($humans[$creditor])){
          $first_val += $humans[$creditor];
        }
      }

      foreach($multicast[$names[$j]["name"]] as $humans){
        $owner = $names[$j]["name"];
        $creditor = $names[$i]["name"];

        if(isset($humans[$creditor])){
          $second_val += $humans[$creditor];
        }
      }

      $first_return_part += $first_val;
      $second_return_part += $second_val;

      if($first_return_part == $second_return_part){
        continue;
      }
      if($first_return_part > $second_return_part){
        $next_part = "";

        $summ = floatval($names[$i]["each_return_part"] - $names[$j]["each_return_part"]);

        if($first_val != 0){
          $summ += $first_val;
          $next_part .= " + $first_val грн";
        }
        if($second_val != 0){
          $summ -= $second_val;
          $next_part .= " - $second_val грн";
        }

        $result[$names[$j]["name"]][] = array(
          "creditor" => $names[$i]["name"],
          "count"    => "({$names[$i]["each_return_part"]} грн - {$names[$j]["each_return_part"]} грн{$next_part})",
          "summ"     => $summ
        );
      }else{
        $next_part = "";

        $summ = floatval($names[$j]["each_return_part"] - $names[$i]["each_return_part"]);

        if($first_val != 0){
          $summ -= $first_val;
          $next_part .= " - $first_val грн";
        }
        if($second_val != 0){
          $summ += $second_val;
          $next_part .= " + $second_val грн";
        }
        $result[$names[$i]["name"]][] = array(
          "creditor" => $names[$j]["name"],
          "count"    => "({$names[$j]["each_return_part"]} грн - {$names[$i]["each_return_part"]} грн{$next_part})",
          "summ"     => $summ
        );
      }
    }
  }

  ?>
  <table cellspacing='0px'>
    <tr>
      <td><b>Хто</b></td>
      <td></td>
      <td><b>Кому</b></td>
      <td><b>Розрахунок</b></td>
      <td><b>Сума</b></td>
    </tr>
  <?
  foreach($result as $human => $val){
    foreach ($val as $creditor_details) {
      ?>
        <tr>
          <td><?=$human?></td>
          <td>=></td>
          <td><?=$creditor_details["creditor"]?></td>
          <td><?=$creditor_details["count"]?></td>
          <td><?=$creditor_details["summ"]?> грн</td>
        </tr>
      <?
    }

  }
  ?></table><?
?>
<br><br>
<form action="trip_pay.php" method="post">
  <button type="accept" name="button">Вернутись назад</button>
</form>
<style>
  body{
    font-family: sans-serif;
  }
  button{
    background-color: #4CAF50;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
  }
  th, td {
    padding: 10px;
  }
  tr:nth-child(even) {background-color: #f2f2f2;}

  @media (max-width: 981px) {
    body{
      font-size: 2em;
    }
    table{
      font-size:1em;
    }
    button{
      font-size: 1em;
    }
    form{
      display: flex;
      justify-content: center;
    }
  }
</style>
