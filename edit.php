<?php
session_start();
require_once "pdo.php";

if (!isset($_SESSION['name']) ) {
    die('ACCESS DENIED');
  }

if ( isset($_POST['cancel'])){
    header('Location: index.php');
    return;
}

if ( ! isset($_GET['profile_id']) ) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
  }

  if ( isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) 
    && isset($_POST['headline']) && isset($_POST['summary']) ) {

    // Data validation
    if(strlen($_POST['first_name']) == 0 || strlen($_POST['last_name']) == 0 
        || strlen($_POST['headline'])==0 || strlen($_POST['summary'])==0) {
        $_SESSION['error'] = 'All fields are required';
        header("Location: edit.php?profile_id=".$_POST['profile_id']);
        return;
    }
    if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        $_SESSION['error'] = "Email address must contain @";
        header("Location: edit.php?profile_id=".$_POST['profile_id']);
        return;
    }
    //Validation for Position
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) ) continue;
        if ( ! isset($_POST['desc'.$i]) ) continue;
    
        $year = $_POST['year'.$i];
        $desc = $_POST['desc'.$i];
    
        if ( strlen($year) == 0 || strlen($desc) == 0 ) {
            $_SESSION['error']="All fields are required";
            header("Location: edit.php?profile_id=".$_POST['profile_id']);
            return;
        }
    
        if ( ! is_numeric($year) ) {
            $_SESSION['error']="Position year must be numeric";
          header("Location: edit.php?profile_id=".$_POST['profile_id']);
          return;
        }
      }
      //Validation for Education
      for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['edu_year'.$i]) ) continue;
        if ( ! isset($_POST['edu_school'.$i]) ) continue;
    
        $edu_year = $_POST['edu_year'.$i];
        $edu_school = $_POST['edu_school'.$i];
    
        if ( strlen($edu_year) == 0 || strlen($edu_school) == 0  ) {
            $_SESSION['error']="All fields are required";
            header("Location: add.php");
            return;
          }
      
          if ( ! is_numeric($edu_year) ) {
            $_SESSION['error']="Education year must be numeric";
            header("Location: add.php");
            return;
          }
      }


    $sql = "UPDATE Profile SET first_name = :first_name,
            last_name = :last_name, email = :email, headline = :headline, summary = :summary
            WHERE profile_id = :profile_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':first_name' => $_POST['first_name'],
        ':last_name' => $_POST['last_name'],
        ':email' => $_POST['email'],
        ':headline' => $_POST['headline'],
        ':summary' => $_POST['summary'],
        ':profile_id' => $_POST['profile_id']));


    // DEL ALL POSITION ENTRIES
    $stmt = $pdo->prepare('DELETE FROM Position WHERE profile_id=:pid');
    $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));

    //Query for Insert Positions 
    $rank = 1;
    for($i=1; $i<=9; $i++) {
        if ( ! isset($_POST['year'.$i]) ) continue;
        if ( ! isset($_POST['desc'.$i]) ) continue;

        $year = $_POST['year'.$i];
        $desc = $_POST['desc'.$i];
        $stmt = $pdo->prepare('INSERT INTO Position
            (profile_id, rank, year, description)
            VALUES ( :pid, :rank, :year, :desc)');

        $stmt->execute(array(
        ':pid' => $_REQUEST['profile_id'],
        ':rank' => $rank,
        ':year' => $year,
        ':desc' => $desc)
        );

        $rank++;
    }

     // DEL ALL EDUCATION ENTRIES
     $stmt_del_education = $pdo->prepare('DELETE FROM Education WHERE profile_id=:pid');
     $stmt_del_education->execute(array( ':pid' => $_REQUEST['profile_id']));

     //INSERT EDUCATIONS ENTRIES
     $rank_edu = 1;
     for($i=1; $i<=9; $i++) {
         if ( ! isset($_POST['edu_year'.$i]) ) continue;
         if ( ! isset($_POST['edu_school'.$i]) ) continue;

         $edu_year = $_POST['edu_year'.$i];
         $edu_school = $_POST['edu_school'.$i];

             $stmt_institution = $pdo->prepare("SELECT * FROM Institution where name = :xyz");
             $stmt_institution->execute(array(":xyz" => $edu_school));
             $row_institution = $stmt_institution->fetch(PDO::FETCH_ASSOC);
             
             //if institution exits
             if ($row_institution) {
                 $institution_id = $row_institution['institution_id'];
                 $stmt = $pdo->prepare('INSERT INTO Education
                 (profile_id, institution_id, rank, year)
                 VALUES ( :pid, :iid, :rank, :year )');
                 $stmt->execute(array(
                 ':pid' => $_REQUEST['profile_id'],
                 ':iid' => $institution_id,
                 ':rank' => $rank_edu,
                 ':year' => $edu_year,
                 ));
                 $rank_edu++;

             }else{
                 //if institution dosent exits, insert institution and get id
                 $stmt_insert_institution = $pdo -> prepare('INSERT INTO Institution (name) VALUES (:name)');
                 $stmt_insert_institution -> execute(array(
                     ':name' => $edu_school
                 ));
                 $institution_id = $pdo->lastInsertId();

                 $stmt = $pdo->prepare('INSERT INTO Education
                 (profile_id, institution_id, rank, year)
                 VALUES ( :pid, :iid, :rank, :year )');
                 $stmt->execute(array(
                 ':pid' => $profile_id,
                 ':iid' => $institution_id,
                 ':rank' => $rank_edu,
                 ':year' => $edu_year,
                 ));
                 $rank_edu++;
             }
     }



    $_SESSION['success'] = 'Profile updated';
    header( 'Location: index.php' ) ;
    return;
   

}





$stmt = $pdo->prepare("SELECT * FROM Profile where profile_id = :xyz");
$stmt->execute(array(":xyz" => $_GET['profile_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Bad value for id';
    header( 'Location: index.php' ) ;
    return;
}

$fn = htmlentities($row['first_name']);
$ln = htmlentities($row['last_name']);
$em = htmlentities($row['email']);
$hl = htmlentities($row['headline']);
$sm = htmlentities($row['summary']);
$profile_id = $row['profile_id'];

$stmt_position = $pdo->prepare("SELECT * FROM Position where profile_id = :xyz");
$stmt_position->execute(array(":xyz" => $_GET['profile_id']));
$row_position = $stmt_position->fetchAll(PDO::FETCH_ASSOC);

$stmt_education = $pdo->prepare("SELECT year, name FROM Education JOIN Institution 
                                  ON Education.institution_id = Institution.institution_id WHERE profile_id = :xyz");
$stmt_education->execute(array(":xyz" => $_GET['profile_id']));
$rows_education = $stmt_education->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html>
<head>
<title>Carlos Segura Vidal's Profile Edit</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
<?php 
    if (isset($_SESSION['name']) ) {
        echo "<h1>Editing Profile for ";
        echo htmlentities($_SESSION['name']);
        echo "</h1>\n";}

    if ( isset($_SESSION['error']) ) {
        echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
        unset($_SESSION['error']);
    }
?>
<form method="post">
    <p>First Name:
    <input type="text" name="first_name" value="<?= $fn ?>" size="60"></p>
    <p>Last Name:
    <input type="text" name="last_name" value="<?= $ln ?>" size="60"></p>
    <p>Email:
    <input type="text" name="email" value="<?= $em ?>" size="30"></p>
    <p>Headline:<br>
    <input type="text" name="headline" value="<?= $hl ?>" size="80"></p>
    <p>Summary:<br>
    <textarea name="summary" rows="8" cols="80"><?php echo $sm; ?></textarea>
    </p><p>
    <p>Education: <input type="submit" id="addEdu" value="+"></p>
    <div id="edu_fields">
        <?php
            $count_Edu = 1;
            foreach ( $rows_education as $row ) {
                echo('<div id=edu'.$count_Edu.'>');
                echo("<p>Year: <input type='text' name=edu_year".$count_Edu." value=".htmlentities($row['year']).">");
                echo('<input type="button" value="-" onclick="$(\'#edu'.$count_Edu.'\').remove();return false;'.'">');
                echo("</p>");
                echo('<p>School: <input type="text" size="80" autocomplete="off" class="school" name="edu_school'.$count_Edu.'" value="'.htmlentities($row['name']).'">');
                echo("</p>");
                echo ('</div>');
                $count_Edu ++;
            }
        ?>
    </div>
    <p>Position: <input type="submit" id="addPos" value="+"></p>
    <div id="position_fields">
        <?php
            $count = 1;
            foreach ( $row_position as $row ) {
                echo('<div id=position'.$count.'>');
                echo("<p>Year: <input type='text' name=year".$count." value=".htmlentities($row['year']).">");
                echo('<input type="button" value="-" onclick="$(\'#position'.$count.'\').remove();return false;'.'">');
                echo("</p>");
                echo('<textarea name="desc'.$count.'"'.' rows="8" cols="80"' .'>');
                echo(htmlentities($row['description']));
                echo('</textarea>');
                echo ('</div>');
                $count ++;
            }
        ?>
    </div>
    <input type="hidden" name="profile_id" value="<?= $profile_id ?>">
    <input type="submit" value="Save">
    <input type="submit" name="cancel" value="Cancel">
    </p>
</form>

<script>
<?php echo("countPos=".$count); ?>;
<?php echo("countEdu=".$count_Edu); ?>

$(document).ready(function(){
    $('#addPos').click(function(event){
        event.preventDefault();
        if ( countPos >= 9 ) {
            alert("Maximum of nine position entries exceeded");
            return;
        }
        countPos++;
        $('#position_fields').append(
            '<div id="position'+countPos+'"> \
            <p>Year: <input type="text" name="year'+countPos+'" value="" /> \
            <input type="button" value="-" onclick="$(\'#position'+countPos+'\').remove();return false;"><br>\
            <textarea name="desc'+countPos+'" rows="8" cols="80"></textarea>\
            </div>');
    });

    $('#addEdu').click(function(event){
        event.preventDefault();
        if ( countEdu >= 9 ) {
            alert("Maximum of nine education entries exceeded");
            return;
        }
        countEdu++;
        $('#edu_fields').append(
            '<div id="edu'+countEdu+'"> \
            <p>Year: <input type="text" name="edu_year'+countEdu+'" value="" /> \
            <input type="button" value="-" onclick="$(\'#edu'+countEdu+'\').remove();return false;"><br>\
            <p>School: <input type="text" size="80" name="edu_school'+countEdu+'" class="school" value="" />\
            </p></div>'
        );

        $('.school').autocomplete({
            source: "school.php"
        });

    });

});

$(document).ready( function () {
  $.getJSON('school.php', function(data) {
    })
  }
);

</script>
</div>
</body>