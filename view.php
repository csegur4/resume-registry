<?php
require_once "pdo.php";
session_start();


if (!isset($_SESSION['name']) ) {
    die('ACCESS DENIED');
  }


if ( ! isset($_GET['profile_id']) ) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
  }
 
  $stmt = $pdo->prepare("SELECT * FROM Profile where profile_id = :xyz");
  $stmt->execute(array(":xyz" => $_GET['profile_id']));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt_position = $pdo->prepare("SELECT * FROM Position where profile_id = :xyz");
  $stmt_position->execute(array(":xyz" => $_GET['profile_id']));
  $rows_position = $stmt_position->fetchAll(PDO::FETCH_ASSOC);

  $stmt_education = $pdo->prepare("SELECT year, name FROM Education JOIN Institution 
                                  ON Education.institution_id = Institution.institution_id WHERE profile_id = :xyz");
  $stmt_education->execute(array(":xyz" => $_GET['profile_id']));
  $rows_education = $stmt_education->fetchAll(PDO::FETCH_ASSOC);


?>




<!DOCTYPE html>
<html>
<head>
<title>Carlos Segura Vidal's Profile Information</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">


<h2>Profile Information</h2>
<ul>
<?php 
    echo("<p>First Name: ".htmlentities($row['first_name'])."</p>");
    echo("<p>Last Name: ".htmlentities($row['last_name'])."</p>");
    echo("<p>Email: ".htmlentities($row['email'])."</p>");
    echo("<p>Headline: <br> ".htmlentities($row['headline'])."</p>");
    echo("<p>Summary: <br> ".htmlentities($row['summary'])."</p>");
    echo("<p>Education:</p><ul>");
    if($rows_education){
        foreach ( $rows_education as $row ) {
            echo("<li>");
            echo($row['year'].": ".$row['name']);
            echo("</li>");
        }
        echo("</ul>");
    }

    if($rows_position){
        echo("<p>Position:</p><ul>");
        foreach ( $rows_position as $row ) {
            echo("<li>");
            echo($row['year'].": ".$row['description']);
            echo("</li>");
        }
        echo("</ul>");
    }


?>
</ul>
<a href="index.php">Done</a> 
</p>
</div>
</body>
</html>