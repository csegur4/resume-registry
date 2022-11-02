<?php
session_start();
require_once "pdo.php";

if (!isset($_SESSION['name']) ) {
    die('ACCESS DENIED');
  }

if (isset($_POST['cancel']) ) {
    header('Location: index.php');
    return;
 }

 if ( isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) 
        && isset($_POST['headline']) && isset($_POST['summary']) ) { 
            if(strlen($_POST['first_name']) == 0 || strlen($_POST['last_name']) == 0 
                        || strlen($_POST['headline'])==0 || strlen($_POST['summary'])==0){
                $_SESSION['error'] = "All fields are required";
                header("Location: add.php");
                return;                
            }

            if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
                $_SESSION['error'] = "Email address must contain @";
                header("Location: add.php");
                return;
            }

            for($i=1; $i<=9; $i++) {
                if ( ! isset($_POST['year'.$i]) ) continue;
                if ( ! isset($_POST['desc'.$i]) ) continue;
            
                $year = $_POST['year'.$i];
                $desc = $_POST['desc'.$i];
            
                if ( strlen($year) == 0 || strlen($desc) == 0 ) {
                    $_SESSION['error']="All fields are required";
                    header("Location: add.php");
                    return;
                }
            
                if ( ! is_numeric($year) ) {
                    $_SESSION['error']="Position year must be numeric";
                  header("Location: add.php");
                  return;
                }
              }

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


        
        $stmt = $pdo->prepare('INSERT INTO Profile
            (user_id, first_name, last_name, email, headline, summary)
            VALUES ( :uid, :fn, :ln, :em, :he, :su)');
          
        $stmt->execute(array(
            ':uid' => $_SESSION['user_id'],
            ':fn' => $_POST['first_name'],
            ':ln' => $_POST['last_name'],
            ':em' => $_POST['email'],
            ':he' => $_POST['headline'],
            ':su' => $_POST['summary'])
          );

            $profile_id = $pdo->lastInsertId();
          // Insert Positions
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
                ':pid' => $profile_id,//$_REQUEST['profile_id']
                ':rank' => $rank,
                ':year' => $year,
                ':desc' => $desc)
                );
                $rank++;
            }

            //Insert Education

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
                        ':pid' => $profile_id,
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
          $_SESSION['success'] = "Profile added";
          header("Location: index.php");
          return;
 }


?>


<!DOCTYPE html>
<html>
<head>
<title>Carlos Segura Vidal's Profile Add</title>
<?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">

<?php
if (isset($_SESSION['name']) ) {
    echo "<h1>Adding Profile for ";
    echo htmlentities($_SESSION['name']);
    echo "</h1>\n";}

if ( isset($_SESSION["error"]) ) {
    echo('<p style="color:red">'.htmlentities($_SESSION["error"])."</p>\n");
    unset($_SESSION["error"]);
}
?>

<form method="post">
    <p>First Name:
    <input type="text" name="first_name" size="60"></p>
    <p>Last Name:
    <input type="text" name="last_name" size="60"></p>
    <p>Email:
    <input type="text" name="email" size="30"></p>
    <p>Headline:<br>
    <input type="text" name="headline" size="80"></p>
    <p>Summary:<br>
    <textarea name="summary" rows="8" cols="80"></textarea>
    <p>Education: <input type="submit" id="addEdu" value="+"></p>
    <div id="edu_fields"></div>
    <p>Position: <input type="submit" id="addPos" value="+"></p>
    <div id="position_fields"></div>
    <input type="submit" value="Add">
    <input type="submit" name="cancel" value="Cancel">
    </p>
</form>

<script>
countPos = 0;
countEdu = 0;

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
</html>