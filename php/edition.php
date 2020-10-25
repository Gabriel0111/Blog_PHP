<?php

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// variables globales
$co = connecter();
$article;
$nom_fichier;

// affichage de l'entête
fd_entete('Édition d\'article', '..', '../css/style.css');

// affichage du contenu de la page
fdl_recuperer_article();
fdl_edition_article();

// pied de page
fd_pied();

// fin du script --> envoi de la page 
ob_end_flush();

/**
 * Affichage du contenu principal de la page
 */
function fdl_edition_article()
{
    echo '<main>',
        '<section>',
        '<h2>Contenu de l\'article</h2>';

    verification_erreurs();

    echo '<form method="POST" action="edition.php">',
        '<table style="width: 90%;">',

        '<tr>',
        '<td style="width: 100px;">Titre : </td>',
        '<td><input type="text" name="titre" value="',
        remplir_form('arTitre'),
        '" style="width: 500px"></td>',
        '</tr>',

        '<tr>',
        '<td>Résumé :</td>',
        '<td><textarea name="resume" style="height: 60px;">',
        remplir_form('arResume'),
        '</textarea></td>',
        '</tr>',

        '<tr>',
        '<td>Texte :</td>',
        '<td><textarea name="texte">',
        remplir_form('arTexte'),
        '</textarea></td>',
        '</tr>',

        '<tr>',
        '<td colspan="2"><input type="submit" name="btnEnregistrer" value="Enregistrer les modifications"><input type="reset" value="Réinitialiser"></td>',
        '</tr>',
        '</table></form></section>',

        '<section>',
        '<h2>Image d\'illustration</h2>',

        afficher_image();
    echo '<p>Les images acceptées sont des fichiers JPG au format paysage avec un ratio de 4/3, de taille 1 Mo maximum. <br/></p>',
        'Choisissez un fichier à parcourir : ',

        '<form method="post" action="edition.php" enctype="multipart/form-data">',
        '<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />',

        '<input type="file" name="image" accept="image/jpeg" >',
        '<input type="submit" name="btnEnvoyer" value="Envoyer l\'image">',
        '</form></section></main>';
}

/**
 * Vérifie chaque composants de la page, en fonction 
 * du formulaire envoyé
 */
function verification_erreurs()
{
    global $article, $nom_fichier;
    $message = 'Erreur lors de la création de l\'article : ';

    $nom_fichier = $article['arID'] . '.jpg';
    $path = realpath('../upload') . '/' . $nom_fichier;

    if (isset($_POST['btnEnregistrer'])) {
        if (!verification_champs())
            affiche_erreur($message, 'Tous les champs doivent être remplis');
        else
            actualiser_article();
    }

    if (isset($_POST['btnEnvoyer'])) {
        $err = array();

        if ($err = verification_image()) {
            $e = '<ul>';
            foreach ($err as $value) {
                $e .= "<li>$value</li>";
            }
            $e .= '</ul>';

            affiche_erreur($message, $e);
            return;
        }

        $f = $_FILES['image'];
        if (!@is_uploaded_file($f['tmp_name'])) {
            affiche_erreur($message, 'Erreur interne de transfert');
            return;
        }

        if (@move_uploaded_file($f['tmp_name'], $path)) {
            affiche_OK($f['name'] . ' a bien été téléversée sur le serveur');
        } else {
            affiche_erreur($message, 'Erreur interne de transfert');
        }
    }
}


/**
 * Vérifie si tous les champs sont remplis.
 *
 * @return  bool    Tous les champs sont remplis ?
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
 * Vérifie si l'image a une taille conforme
 * 
 * @return  array   $err    Contient toutes les erreurs potentielles
 *                          résultantes du contrôle de l'image
 */
function verification_image()
{
    $err = array();
    if (isset($_FILES['image'])) {
        $f = $_FILES['image'];
        switch ($f['error']) {
            case 1:
            case 2:
                $err[] = $f['name'] . ' est trop grosse.';
                break;
            case 3:
                $err[] = 'Erreur de transfert de ' . $f['name'];
                break;
            case 4:
                $err[] = 'Le fichier ' . $f['name'] . ' est introuvable.';
                break;
        }

        // Vérification supplémentaire au cas où l'utilisateur aurait supprimer
        // la balise hiddent ayant l'attribut MAX_FILE_SIZE
        if ($_FILES['image']['size'] >= 1000000)
            $err[] = $f['name'] . ' est tres tres grosse.';

        // Arrêt de la recherche des erreurs, le fichier n'étant pas versé
        // sur le serveur
        if (count($err))
            return $err;

        $check = getimagesize($_FILES["image"]["tmp_name"]);


        $ok = ($check[1] ? $check[0] / $check[1] === (4 / 3) : false);

        if ($ok === false)
            $err[] = 'L\'image ne correspond pas à un ratio de 4/3';

        return $err;
    }
}

/**
 * Affiche l'image si elle existe sur le serveur, et sinon
 * affiche l'image par défaut.
 */
function afficher_image()
{
    global $nom_fichier;
    $path = "../upload/$nom_fichier";

    if (file_exists('../upload/' . $nom_fichier))
        echo '<p><img src="' . $path . '" alt="Photo d\'illustration pour l\'article ' . $nom_fichier . '"</p>';
    else
        echo '<p><img src="../images/none.jpg" alt="aucune image" /></p>';
}

/**
 * Initialise l'object $article en récupérant les données
 * de l'article depuis la BDD grâce à l'identifiant présent
 * sur la variable $_SESSION.
 */
function fdl_recuperer_article()
{
    global $co, $article;
    $id = '';

    if (isset($_GET['id'])) {
        global $id;
        $id = $_SESSION['arID'] = decrypterURL($_GET['id']);
    } else {
        global $id;
        $id = $_SESSION['arID'];
    }

    $sql = "SELECT * from `article` WHERE arID = $id";
    $res = $co->query($sql) or bd_erreur($co, $sql);

    $article = mysqli_fetch_assoc($res);
    $res->free();
}

/**
 * Remplit les champs, en se basant sur les données 
 * remplit dans la page nouveau.php
 * 
 * @param   $champ  string  Indique le champ à remplir
 */
function remplir_form($champ)
{
    global $article;
    echo $article["$champ"];
}

/**
 * Met à jour l'article face à la BDD.
 */
function actualiser_article()
{
    global $co, $article;

    $articleProtege = proteger_entree($co, $_POST);
    $arDatePub = date("YmdHi");

    $sql = "UPDATE `article`
            SET arTitre = '{$articleProtege['titre']}',
                arResume = '{$articleProtege['resume']}',
                arTexte = '{$articleProtege['texte']}',
                arDateModification = {$arDatePub}
            WHERE arID = {$article['arID']}";
    $res = $co->query($sql) or bd_erreur($co, $sql);

    affiche_OK('L\'article a bien été actualisé');

    // Réactualise la variable $article
    // pour mettre à jour l'affichage des champs
    fdl_recuperer_article();
}
