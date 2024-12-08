<?php
$zprava = "";
$rezervace = [];

if (file_exists('rezervace.json')) {
    $rezervace = json_decode(file_get_contents('rezervace.json'), true);
}

usort($rezervace, function ($a, $b) {
    return strtotime($a['datum']) - strtotime($b['datum']);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pridat'])) {
        $mistnost = $_POST['mistnost'];
        $datum = $_POST['datum'];
        $zacatek = $_POST['zacatek'];
        $konec = $_POST['konec'];
        $jmeno = $_POST['jmeno'];

        if (!$mistnost || !$datum || !$zacatek || !$konec || !$jmeno) {
            $zprava = "Všechna pole musí být vyplněna.";
        } else {
            $zacatek_time = DateTime::createFromFormat('H:i:s', $zacatek);
            $konec_time = DateTime::createFromFormat('H:i:s', $konec);

            if ($zacatek_time >= $konec_time) {
                $zprava = "Čas začátku musí být před časem konce.";
            } else {
                $soucasne_datum = new DateTime();
                $rezervacni_datum = DateTime::createFromFormat('Y-m-d', $datum);

                if ($rezervacni_datum < $soucasne_datum) {
                    $zprava = "Datum rezervace musí být v budoucnosti.";
                } else {
                    $je_konflikt = false;
                    foreach ($rezervace as $rezervace_item) {
                        if ($rezervace_item['datum'] === $datum) {
                            if (
                                ($zacatek_time >= DateTime::createFromFormat('H:i:s', $rezervace_item['zacatek']) && $zacatek_time < DateTime::createFromFormat('H:i:s', $rezervace_item['konec'])) ||
                                ($konec_time > DateTime::createFromFormat('H:i:s', $rezervace_item['zacatek']) && $konec_time <= DateTime::createFromFormat('H:i:s', $rezervace_item['konec']))
                            ) {
                                $je_konflikt = true;
                                break;
                            }
                        }
                    }

                    if ($je_konflikt) {
                        $zprava = "Místnost je v tomto čase již obsazena.";
                    } else {
                        $nova_rezervace = [
                            'id' => count($rezervace) + 1,
                            'mistnost' => $mistnost,
                            'datum' => $datum,
                            'zacatek' => $zacatek,
                            'konec' => $konec,
                            'jmeno' => $jmeno
                        ];
                        $rezervace[] = $nova_rezervace;
                        file_put_contents('rezervace.json', json_encode($rezervace, JSON_PRETTY_PRINT));
                        $zprava = "Rezervace byla úspěšně přidána.";
                    }
                }
            }
        }
    }

    if (isset($_POST['smazat'])) {
        $rezervace_id = $_POST['rezervace_id'];
        
        foreach ($rezervace as $klic => $rezervace_item) {
            if ($rezervace_item['id'] == $rezervace_id) {
                unset($rezervace[$klic]);
                file_put_contents('rezervace.json', json_encode(array_values($rezervace), JSON_PRETTY_PRINT)); 
                $zprava = "Rezervace byla úspěšně zrušena.";
                break;  
            }
        }
        
        if (!isset($zprava)) {
            $zprava = "Rezervace s tímto ID nebyla nalezena.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace místností</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <h1>Rezervace místností</h1>

    <?php if ($zprava): ?>
        <p class="zprava"><?= htmlspecialchars($zprava) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="mistnost">Název místnosti:</label>
            <input type="text" id="mistnost" name="mistnost">
        </div>
        <div class="form-group">
            <label for="datum">Datum:</label>
            <input type="date" id="datum" name="datum">
        </div>
        <div class="form-group">
            <label for="zacatek">Začátek:</label>
            <input type="text" id="zacatek" name="zacatek" pattern="([01]?[0-9]|2[0-3]):([0-5]?[0-9]):([0-5]?[0-9])" placeholder="hh:mm:ss">
        </div>
        <div class="form-group">
            <label for="konec">Konec:</label>
            <input type="text" id="konec" name="konec" pattern="([01]?[0-9]|2[0-3]):([0-5]?[0-9]):([0-5]?[0-9])" placeholder="hh:mm:ss">
        </div>
        <div class="form-group">
            <label for="jmeno">Vaše jméno:</label>
            <input type="text" id="jmeno" name="jmeno">
        </div>
        <button type="submit" name="pridat">Přidat rezervaci</button>
    </form>

    <h2>Seznam rezervací</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Místnost</th>
            <th>Datum</th>
            <th>Začátek</th>
            <th>Konec</th>
            <th>Jméno</th>
        </tr>
        <?php foreach ($rezervace as $rezervace_item): ?>
            <tr>
                <td><?= htmlspecialchars($rezervace_item['id']) ?></td>
                <td><?= htmlspecialchars($rezervace_item['mistnost']) ?></td>
                <td><?= htmlspecialchars((new DateTime($rezervace_item['datum']))->format('d/m/Y')) ?></td>
                <td><?= htmlspecialchars($rezervace_item['zacatek']) ?></td>
                <td><?= htmlspecialchars($rezervace_item['konec']) ?></td>
                <td><?= htmlspecialchars($rezervace_item['jmeno']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Zrušení rezervace</h2>
    <form method="post">
        <div class="form-group">
            <label for="rezervace_id">ID rezervace:</label>
            <input type="number" id="rezervace_id" name="rezervace_id" required>
        </div>
        <button type="submit" name="smazat">Zrušit rezervaci</button>
    </form>
</body>
</html>
