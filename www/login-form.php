<?php
    ini_set("session.gc_maxlifetime", 900);
    ini_set("session.cookie_lifetime", 900);
    session_start();
    require "db.php";
    require "farbenblind_modus.php";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farbwechsel'])) {
        farbwechsel();
    }
    $modeClass = farbModus();
    
    // Login check und ggf Weiterleitung
    if (!isset($_SESSION["isLoggedIn"])){
        $_SESSION["isLoggedIn"] = false;
    }
    if (isset($_SESSION["isLoggedIn"])){
        if ($_SESSION["isLoggedIn"] == true) {
            header("Location: zeiterfassung.php");
        }
    }
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login X Logistics</title>
        <link rel="stylesheet" href="css/login-styles.css">
        <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    </head>
    <body class="<?php echo $modeClass; ?>">
        <header>
            <nav class="nav-box">
                <form method="post">
                    <button id="auge-button" type="submit" name="farbwechsel"></button>
                </form>
            </nav>
        </header>
        <div class="main-content">
            <main>
                <div class="xform">
                    <h1>Login</h1>
                    <form method="POST">
                        <label for="username">Benutzername:</label>
                        <input type="text" id="username" name="username" required>
                        <label for="password">Passwort:</label>
                        <input type="password" id="password" name="password" required>
                        <button id="form-button" type="submit">Anmelden</button>
                    </form>
                    <?php
                        // SQL datenabruf
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['farbwechsel'])) {
                            $user = $_POST['username'];
                            $passwort = $_POST['password'];
                            $sql = "SELECT * FROM Userlogin WHERE User = :user";
                            $stmt = $pdo->prepare($sql);
                            $stmt->bindParam(':user', $user);
                            $stmt->execute();
                            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($userData && password_verify($passwort, $userData['Passwort'])) {
                                // Valide eingaben
                                if ($userData['Status'] === 'member' || $userData['Status'] === 'admin') {
                                    $_SESSION['UserID'] = $userData['UserID'];
                                    if ($_SESSION["isLoggedIn"] == false) {
                                        $_SESSION["isLoggedIn"] = true;
                                    }

                                    if ($userData['Status'] === 'admin') {
                                        // Admin Logging in.
                                        header("Location: dashboard_admin.php");
                                    } else {
                                        // Random User Logging in.
                                        header("Location: zeiterfassung.php");
                                    }
                                    
                                } else {
                                    // User gesperrt
                                    echo '<p class="warning">Ihr Konto ist derzeit ' . htmlspecialchars($userData['Status']) . '!<br>Warte bitte auf die Freischaltung durch einen Administrator.</p>';
                                }
                            } else {
                                // Eingaben Falsch
                                echo '<p class="warning">Benutzername oder Passwort ist falsch!</p>';
                            }
                        }
                    ?>
                    <p>
                        Noch kein Konto? <a href="register-form.php">Registrieren</a> </br>
                        Passwort vergessen? <a href="password_reset.php">Zurücksetzen</a>
                    </p>
                </div>
                <div class="xlogo">
                    <?php
                            echo $_SESSION['farbenblind_modus'] ?  '<img src="images/xlogo_bg_auge.png">' : '<img src="images/xlogo_bg.png">'; 
                        ?>
                </div>
            <main>
        </div>
    </body>
</html>