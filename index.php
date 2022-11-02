<?php
    session_start();
    require_once "pdo.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Carlos Segura Vidal - Resume Registry</title>
    <?php require_once "bootstrap.php"; ?>
</head>
<body>
<div class="container">
    <h1>Carlos Segura Vidal's Resume Registry</h1>

<?php
    if(isset($_SESSION['success'])){
        echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
        unset($_SESSION['success']);
    }

    if ( !isset($_SESSION['name'])){
        echo "<p><a href='login.php'>Please log in</a></p>";
    }else {
        echo "<p><a href='logout.php'>Logout</a></p>";
        $stmt = $pdo->query("SELECT first_name, last_name, headline, profile_id FROM Profile");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            echo('<table border="1">'."\n");
            echo('<tr><th>Name</th><th>Headline</th><th>Action</th></tr>');
            $stmt = $pdo->query("SELECT first_name, last_name, headline, profile_id FROM Profile");
            while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
                echo "<tr><td>";
                echo('<a href="view.php?profile_id='.$row['profile_id'].'">'.htmlentities($row['first_name'])." ".htmlentities($row['last_name'])."</a>");
                echo("</td><td>");
                echo(htmlentities($row['headline']));
                echo("</td><td>");
                echo('<a href="edit.php?profile_id='.$row['profile_id'].'">Edit</a> / ');
                echo('<a href="delete.php?profile_id='.$row['profile_id'].'">Delete</a>');
                echo("</td></tr>\n");
            }
            
        }
        echo "<p><a href='add.php'>Add New Entry</a></p>";
    } 

?>
    <p>Note: Your implementation should retain data across multiple logout/login sessions. 
    This sample implementation clears all its data periodically - which you should not do in your implementation.</p>

</div>
</body>