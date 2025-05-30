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


    // Startzeit aus Sitzung oder Datenbank abrufen, wenn keine Session festgelegt ist
    if (!isset($_SESSION['startzeit'])) {
        $MitarbeiterID = $_SESSION['UserID'];
        $sqlStart = "SELECT startzeit FROM Zeiterfassung WHERE MitarbeiterID = :MitarbeiterID AND status = 'aktiv' AND endzeit IS NULL ORDER BY startzeit DESC LIMIT 1";
        $stmtStart = $pdo->prepare($sqlStart);
        $stmtStart->bindParam(':MitarbeiterID', $MitarbeiterID);
        $stmtStart->execute();
        $result = $stmtStart->fetch(PDO::FETCH_ASSOC);
        $startzeit = $result ? $result['startzeit'] : null;
        // Wenn Startzeit gefunden, in der Session speichern für schnelleren Zugriff
        if ($startzeit) {
            $_SESSION['startzeit'] = $startzeit;
        }
    } else {
        $startzeit = $_SESSION['startzeit'];
    }

    // Wenn nicht eingeloggt wird hier direkt zur Login-Seite gesprungen.
    if (!isset($_SESSION["isLoggedIn"]) or $_SESSION["isLoggedIn"] == false) {
        header("Location: login-form.php");
    }

    // Funktion zum Session-Logout und Session-Destroy.
    function logout_action()
    {
        if (isset($_SESSION["isLoggedIn"])) {
            if ($_SESSION["isLoggedIn"] == true) {
                $_SESSION["isLoggedIn"] = false;
                session_destroy();
                header("Location: login-form.php");
            }
        }
    }

    // POST Logout-Abfrage.
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
        logout_action();
    }

    // MitarbeiterID aus der Session holen
    $MitarbeiterID = $_SESSION['UserID'];

    // Abfrage, die alle Zeiterfassungslogs nach Datum und Dauer für den eingeloggten Mitarbeiter holt
    $sql = "SELECT DATE(startzeit) AS datum, SEC_TO_TIME(SUM(dauer)) AS gesamtzeit FROM Zeiterfassung WHERE MitarbeiterID = :MitarbeiterID GROUP BY DATE(startzeit) ORDER BY datum DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':MitarbeiterID', $MitarbeiterID);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //Abfrage um den Namen des Mitarbeiters in die Tabellenüberschrift der Zeilogs zu setzen
    $sqlName = "SELECT vorname, nachname FROM Mitarbeiter WHERE MitarbeiterID = :MitarbeiterID";
    $stmtName = $pdo->prepare($sqlName);
    $stmtName->bindParam(':MitarbeiterID', $MitarbeiterID);
    $stmtName->execute();
    $mitarbeiter = $stmtName->fetch(PDO::FETCH_ASSOC);
    // Variablen für Vor- und Nachnamen setzen mit Fallback
    $vorname = $mitarbeiter['vorname'] ?? 'Mitarbeiter';
    $nachname = $mitarbeiter['nachname'] ?? '';
?>

<!DOCTYPE html>
<html lang="de">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Zeiterfassung X Logistics</title>
        <link rel="stylesheet" href="css/styles.css">
        <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    </head>
    <body class="<?php echo $modeClass; ?>">  
        <header>
            <nav class="nav-box">
                <button onclick="window.location.href='lohnabrechnung.php'">Abrechnungen</button>
                <form method="post">
                    <button type="submit" name="logout">Logout</button>
                </form>
                <form method="post">
                    <button id="auge-button" type="submit" name="farbwechsel"></button>
                </form>
            </nav>
        </header>       
        <div class="dashboard-main">
            <div class="xlogo">
                <?php
                    echo $_SESSION['farbenblind_modus'] ? '<img src="images/xlogo_bg_auge.png">' : '<img src="images/xlogo_bg.png">'; 
                ?>
            </div>
            <h1>Zeiterfassung</h1>

            <!-- Nachricht / Status -->
            <div id="statusMessage"></div>

            <!-- Anzeige der Start- und Endzeit -->
            <div class="time-display">
                <p>Startzeit: <span id="startzeit"><?php echo $startzeit ? date('d.m.Y H:i:s', strtotime($startzeit)) : '-'; ?></span></p>
                <p>Endzeit: <span id="endzeit">-</span></p>
            </div>

            <!-- Buttons zum Starten und Stoppen der Zeiterfassung -->
            <button onclick="startZeiterfassung()">Zeiterfassung starten</button>
            <button onclick="stopZeiterfassung()">Zeiterfassung stoppen</button>

            <h2>Zeiterfassungs-Logs für <?php echo htmlspecialchars($vorname . ' ' . $nachname); ?></h2>

            <!-- Tabelle zur Anzeige der Zeiterfassungsdaten -->
            <table>
                <tr>
                    <th>Datum</th>
                    <th>Gesamtzeit</th>
                </tr>

                <?php
                if (!empty($logs)) {
                    foreach ($logs as $log) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($log['datum']) . "</td>";
                        echo "<td>" . htmlspecialchars($log['gesamtzeit']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>Keine Zeiterfassungsdaten verfügbar.</td></tr>";
                }
                ?>
            </table>
            <script>
                // Funktion zum Starten der Zeiterfassung
                function startZeiterfassung() {
                    fetch('start_zeiterfassung.php')
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('statusMessage').innerText = data;

                            if (data.includes("Zeiterfassung gestartet")) {
                                // Startzeit setzen, wenn erfolgreich
                                const currentDateTime = new Date().toLocaleString('de-DE'); // Datum und Uhrzeit im deutschen Format
                                document.getElementById('startzeit').innerText = currentDateTime;
                                document.getElementById('endzeit').innerText = "-";
                            }
                        });
                }

                // Funktion zum Stoppen der Zeiterfassung
                function stopZeiterfassung() {
                    fetch('stop_zeiterfassung.php')
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('statusMessage').innerText = data;

                            if (data.includes("Zeiterfassung gestoppt")) {
                                // Endzeit setzen, wenn erfolgreich
                                const currentDateTime = new Date().toLocaleString('de-DE'); // Datum und Uhrzeit im deutschen Format
                                document.getElementById('endzeit').innerText = currentDateTime;
                            }
                        });
                }
            </script>
        </div>
    </body>
</html>