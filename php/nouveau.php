<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// variables globales
$co = connecter();

// affichage de l'entête
fd_entete('Nouvel article', '..', '../css/style.css');

// affichage du contenu de la page
fdl_nouvel_article();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function fdl_nouvel_article()
{
    echo '<main>',
        '<section>',
        '<h2>Contenu de l\'article</h2>';

    verification_erreurs();

    echo '<form method="POST" action="nouveau.php">',
        '<table style="width: 90%;">',

        '<tr>',
        '<td style="width: 100px;">Titre : </td>',
        '<td><input type="text" name="titre" value="',
        remplir_form('titre'),
        '" style="width: 500px"></td>',
        '</tr>',

        '<tr>',
        '<td>Résumé :</td>',
        '<td><textarea name="resume" style="height: 60px;">',
        remplir_form('resume'),
        '</textarea></td>',
        '</tr>',

        '<tr>',
        '<td>Texte :</td>',
        '<td><textarea name="texte">',
        remplir_form('texte'),
        '</textarea></td>',
        '</tr>',

        '<tr>',
        '<td colspan="2"><input type="submit" name="btnCreer" value="Créer l\'article"><input type="reset" value="Réinitialiser"></td>',
        '</tr>',
        '</table></section></main>';
}


/**
 * Vérifie les champs ou envoie vers édition si 
 * aucune erreur n'a été trouvée.
 */
function verification_erreurs()
{
    if (isset($_POST['btnCreer'])) {
        if (verification_champs())
            editer_article();
        else
            affiche_erreur('Erreur lors de la création de l\'article : ', 'Tous les champs doivent être remplis');
    }
}

/**
 * Vérifie si tous les champs sont remplis.
 */
function verification_champs()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            isset($_POST['titre']) && empty($_POST['titre']) ||
            isset($_POST['resume']) && empty($_POST['resume']) ||
            isset($_POST['texte']) && empty($_POST['texte'])
        )
            return false;
        return true;
    }
}

/**
 * Protège chaque entrée de la variable $_POST, et
 * réattribue le résultat renvoyé par la fonction
 * à la valeur de $_POST elle-même.
 */
function protection_entrees()
{
    global $co;
    foreach ($_POST as $key => $value)
        $_POST[$key] = proteger_entree($co, $value);
}

function editer_article()
{
    global $co;

    $sql = "SELECT MAX(arID)+1  FROM `article`";
    $res = $co->query($sql) or bd_erreur($co, $sql);
    $arID = intval(mysqli_fetch_row($res)[0]);
    $arDatePub = date("YmdHi");

    protection_entrees();

    $sql = 'INSERT INTO `article`' .
        " VALUES ( '{$arID}', '{$_POST['titre']}', '{$_POST['texte']}', '{$_POST['resume']}', '{$arDatePub}', '{$arDatePub}', '{$_SESSION['pseudo']}' )";
    $res = $co->query($sql) or bd_erreur($co, $sql);

    unset($_POST);
    $_SESSION['arID'] = $arID;
    header('Location: edition.php');
}

/**
 * Remplit les champs, en se basant sur les données 
 * remplit dans la page nouveau.php
 * 
 * @param   $champ  string  Indique le champ à remplir
 */
function remplir_form($champ)
{
    if (isset($_POST) && !empty($_POST)) {
        return $_POST[$champ];
    }
}
