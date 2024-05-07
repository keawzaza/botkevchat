<?php
 // รับค่า POST Request จาก LINE
  $LINEData = file_get_contents('php://input');
 //ทำการเรียก func json_decode ตัวข้อมูลที่ได้มาเนื่องจากมีรูปเเบบเป็น JSON ** อย่าลืมใส่ true ด้วยเพื่อให้ข้อมูลออกมาในรูปเเบบของ Array Object
  $jsonData = json_decode($LINEData,true);

  //เก็บ ReplyToken
  $replyToken = $jsonData["events"][0]["replyToken"];
  // เก็บ userID
  $userID = $jsonData["events"][0]["source"]["userId"];
  // เก็บ text
  $text = $jsonData["events"][0]["message"]["text"];
  // เก็บ Timestamp
  $timestamp = $jsonData["events"][0]["timestamp"];

  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "line";
  $mysql = new mysqli($servername, $username, $password, $dbname);
  mysqli_set_charset($mysql, "utf8");
// ตรวจสอบว่ามี Error จากการเชื่อมต่อหรือไม่ ลองเข้า URL ที่มาถึง php ตัวนี้ เเละดู Result ที่ออกมาว่ามี Error จากขั้นตอนการเชื่อมต่อ MySQLมั้ย
  if ($mysql->connect_error){
  $errorcode = $mysql->connect_error;
  print("MySQL(Connection)> ".$errorcode);
  }

// Function นี้เอาไว้ใช้สำหรับส่ง POST Request กลับไปที่ LINE โดยวิธีการใช้คือ
// ตรง เรียก Function นี้โดยให้ค่า $replyJson เป็น JSON Data ที่จะส่งกลับไปที่ LINE
// เเละ $sendInfo เป็น Array ที่มีค่า URL (ที่อยู่ที่จะส่งกลับไปที่ LINE [Reply,Push]) เเละ Accesstoken ใน Array นั้น

  function sendMessage($replyJson, $sendInfo){
          $ch = curl_init($sendInfo["URL"]);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLINFO_HEADER_OUT, true);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'Authorization: Bearer ' . $sendInfo["AccessToken"])
              );
          curl_setopt($ch, CURLOPT_POSTFIELDS, $replyJson);
          $result = curl_exec($ch);
          curl_close($ch);
    return $result;
  }
//เก็บ ข้อมูลใน Table LOG
  $mysql->query("INSERT INTO `LOG`(`UserID`, `Text`, `Timestamp`) VALUES ('$userID','$text','$timestamp')");
//เรียกดูข้มูลใน Table Customer
  $getUser = $mysql->query("SELECT * FROM `Customer` WHERE `UserID`='$userID'");
  $getuserNum = $getUser->num_rows;
  $replyText["type"] = "text";
  //ตรวจว่า พบข้อมูลจากการ Query หรือไม่
  if ($getuserNum == "0"){
  //ไม่พบ

  // ตรงนี้ป็นส่วนข้อความที่เราต้องการจะ Reply ครับ
  //ตรงนี้เป็น Type ซึ่งผมจะส่งเป็น Text
    $replyText["type"] = "text";
  //ตรงนี้เป็น Text ที่เราต้องการจะ Reply ครับ
    $replyText["text"] = "สวัสดีคับบบบ";

  // Note: ตรงส่วนนี้เราสามารถเเก้ไขให้เป็นข้อความประเภทอื่นๆได้เช่น
  // Flex Message, Image, Sticker ครับลองไปปรับใช้กันดูนะครับ
  } else {
  //พบ
  //นำข้อมูลมา Loop เพื่อเอาค่าออกมาใส่ตามตัวเเปรต่างๆ
    while($row = $getUser->fetch_assoc()){
      $Name = $row['Name'];
      $Surname = $row['Surname'];
      $CustomerID = $row['CustomerID'];
    }
    $replyText["type"] = "text";
    $replyText["text"] = "สวัสดีคุณ $Name $Surname (#$CustomerID)";
  }
  //ข้อมูลสำหรับการทำ POST Request
  $lineData['URL'] = "https://api.line.me/v2/bot/message/reply";
  $lineData['AccessToken'] = "OFvAmyeycV9atKHD7us21lzfwsG3NJGFMXTRc+cpWwY1EiKknhBihm7CW7rMjoOExw/7w0iT6CwRwrFW7pXGZ296IuylEbnVKcTzPXCcjyFpEn4X1QeTYvVEoUT9xAVRwQjliEEoP4whuGoGBoMLbAdB04t89/1O/w1cDnyilFU=";

  //สร้างตัวเเปรเป็น Array ที่มี replyToken ก่อน
  $replyJson["replyToken"] = $replyToken;
  //จากนั้นนำข้อความที่เราทำไว้ด้านบนลงมาใส่ในตัวเเปรนี้อีกที
  $replyJson["messages"][0] = $replyText;
  //เเละทำมันเป็น JSON Data ซะ
  $encodeJson = json_encode($replyJson);

  //ทำการเรียก Function sendMessage ที่เราเขียนไว้ด้านบน
  $results = sendMessage($encodeJson,$lineData);
  //เอา สิ่งที่ Function นี้ Return กลับมาดูเผื่อมันมี Error โดยการ echo มันออกมา
  echo $results;
  http_response_code(200);