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
fd_entete('Mon Compte', '..', '../css/style.css');

// affichage du contenu de la page
fdl_compte();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();

/**
 * Affichage du contenue principal de la page
 */
function fdl_compte()
{
    echo '<main>',
        '<section>',
        '<h2>Informations personnelles</h2>',
        print_r($_POST);

    if (isset($_POST['btnCreer'])) {
        if (verification_champs())
            editer_article();
        else
            affiche_erreur('Erreur lors de la création de l\'article : ', 'Tous les champs doivent être remplis');
    }

    echo '<p>Vous pouvez modifier les informations suivantes.</p>',
        '<form action="inscription.php" method="post">',
        '<table>',

        '<tr>',
        '<td><label for="nom">Votre nom : </td>',
        '<td><input type="text" name="nom" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="prenom">Votre prénom : </td>',
        '<td><input type="text" name="prenom" /></td>',
        '</tr>',

        '<tr>',
        '<td><label for="date_naissance">Votre date de naissance : </td>',
        '<td><select name="jour">';

    for ($i = 1; $i <= 31; ++$i)
        echo "<option value='$i'>$i</option>";
    echo '</select>',

        fd_creer_liste_mois('mois', 'janvier');

    echo '<select name="annee">';
    for ($i = 2019; $i >= 1950; --$i)
        echo "<option value='$i'>$i</option>";
    echo '</select></tr>',

        '<tr>',
        '<td><label for="email">Votre email : </td>',
        '<td><input type="email" name="email" value="',
        '"/></td>',
        '</tr>',


        '<tr>',
        '<td colspan="2"><input type="submit" name="btnCreer" value="Créer l\'article"><input type="reset" value="Réinitialiser"></td>',
        '</tr>',
        '</table></section></main>';
}

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
