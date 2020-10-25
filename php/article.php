<?php

use function PHPSTORM_META\type;

require_once('./echo.php');
require_once('./bibli.php');

// bufferisation des sorties
ob_start();

// démarrage de la session 
session_start();

// variables globales
$idArticle;
$idArticleCrypte;
$co;

// si il y a une autre clé que id dans $_GET, l'utilisateur est redirigé vers index.php
if (!fd_controle_parametres('get', array(), array('id'))) {
    fd_exit_session();
}

// affichage de l'entête
fd_entete('L\'actu', '..', '../css/style.css');

// affichage du contenu (article + commentaires)
fdl_affichage_article();

// pied de page
fd_pied();

// fin du script
ob_end_flush();


/**
 * Affichage de l'article et de ses commentaires
 *
 * NB : habituellement, on ferait plutôt une seule requête pour récupérer
 * l'article et ses commentaires associés. 
 * Toutefois ici, on a besoin de vérifier que l'article est bien valide avant de 
 * pouvoir faire l'insertion d'un commentaire. De plus, on souhaite afficher le commentaire 
 * qui vient d'être ajouté, ou ne pas afficher celui qui vient d'être supprimé.
 *		
 * Donc c'est plus simple de faire le traitement en 3 étapes en utilisant 2 requêtes SQL :
 * - 1 : vérification que l'article d'ID passé dans l'URL existe et affichage de l'article (=> 1ère requête SQL)
 * - 2 : traitement des publication / suppression de commentaires (=> 2e devoir)
 * - 3 : récupération et affichage des commentaires (=> 2ème requête SQL)
 */
function fdl_affichage_article()
{
    global $idArticle, $idArticleCrypte, $co;

    /* ----------- Etape 1 --------------------------- */

    // vérification du format du paramètre dans l'URL
    if (!isset($_GET['id'])) {
        fd_afficher_erreur('Identifiant d\'article non reconnu.');
        return;
    }

    $id = decrypterURL($_GET['id']);

    if (!estEntier($id) || $id <= 0) {
        fd_afficher_erreur('Identifiant d\'article non reconnu.');
        return;
    }
    $id = (int) $id;

    // Initialise ces variables globales pour un usage ultérieur.
    $idArticle = $id;
    $idArticleCrypte = crypterURL($id);;

    // ouverture de la connexion à la base de données
    $co = connecter();

    // Récupération de l'article concerné
    $sql = "SELECT *  
            FROM (article INNER JOIN utilisateur ON arAuteur = utPseudo)
            WHERE arID = {$id}";

    $res = $co->query($sql) or bd_erreur($co, $sql);

    // pas d'articles --> fin de la fonction
    if ($res->num_rows == 0) {
        fd_afficher_erreur('Identifiant d\'article non reconnu.');
        $res->free();
        $co->close();
        return;
    }

    // ---------------- GENERATION DE L'ARTICLE ------------------

    // affichage de l'article et des commentaires associés
    echo '<main id="actus">';

    $tab = $res->fetch_assoc();

    $res->free();

    // utilisé pour le test suivant (les accents ne doivent pas être remplacés par leur entité HTML)
    $auteur = $tab['arAuteur'];

    if (isset($_SESSION['pseudo']) && !empty($_SESSION['pseudo']) && $_SESSION['pseudo'] === $auteur)
        echo "<p class=\"bandeau\">Vous êtes l'auteur de cet article, <a href=\"edition.php?id=$idArticleCrypte\">cliquez ici pour le modifier.</a></p>";

    $tab = proteger_sortie($tab);

    $imgFile = "../upload/{$id}.jpg";

    // génération du bloc <article>
    echo '<article>',
        '<h3>',
        $tab['arTitre'],
        '</h3>',
        ((file_exists($imgFile)) ? "<img src='{$imgFile}' alt=\"Photo d\'illustration | {$tab['arTitre']}\">" : ''),
        fd_traiter_texte($tab['arTexte']),
        "<footer>Publié par <a href='../php/redaction.php#{$tab['utPseudo']}'>{$tab['utPrenom']} {$tab['utNom']}</a> le ",
        fd_affiche_date($tab['arDatePublication']);

    // ajout dans le pied d'article d'une éventuelle date de modification
    if ($tab['arDateModification']) {
        echo ' modifié le ' . fd_affiche_date($tab['arDateModification']);
    }

    // fin du bloc <article>
    echo '</footer>',
        '</article>';

    /* ----------- Etape 2 --------------------------- */

    // Réponse aux formulaires potentiels
    if (isset($_POST['btnSupprimer']))
        supprimer_comment_bd();
    if (isset($_POST['btnPublier']))
        publier_comment_bd();

    // Vérification de la connexion de l'utilisateur
    $estConnecte = $estRedacteur = false;

    if (isset($_SESSION['pseudo'])) {
        $estConnecte = true;
        if (isset($_SESSION['estRedacteur']) && !empty($_SESSION['estRedacteur']))
            $estRedacteur = true;
    }

    /* ----------- Etape 3 --------------------------- */

    // Récupération des commentaires de l'article concerné
    $sql = "SELECT * FROM commentaire WHERE coArticle = $id";

    $res = $co->query($sql) or bd_erreur($co, $sql);

    // Génération du début de la zone de commentaires
    echo '<section id="comments">',
        '<h2>Réactions</h2>';

    // s'il existe des commentaires, on les affiche un par un.
    if ($res->num_rows > 0) {
        echo '<ul>';
        while ($tab = $res->fetch_assoc()) {
            echo '<li>';

            // Si l'utilisateur est rédacteur ou auteur du commentaire,
            // on affiche le bouton 'Supprimer le commentaire'
            if ($estRedacteur || isset($_SESSION['pseudo']) && !empty($_SESSION['pseudo']) && $_SESSION['pseudo'] === $tab['coAuteur'])
                affiche_btn_supprimer($tab);

            echo '<p>Commentaire de <strong>', proteger_sortie($tab['coAuteur']), '</strong>, le ',
                fd_affiche_date($tab['coDate']),
                '</p>',
                '<blockquote>',
                proteger_sortie($tab['coTexte']),
                '</blockquote>',
                '</li>';
        }
        echo '</ul>';
    }
    // sinon on indique qu'il n'y a pas de commentaires
    else {
        echo '<p>Il n\'y a pas de commentaires à cet article. </p>';
    }

    // libération des ressources
    $res->free();

    if ($estConnecte)
        affiche_poster_comments();
    else
        affiche_connexion();

    echo
        '</section></main>';

    // fermeture de la connexion à la base de données
    $co->close();
}

/**
 * Affiche le bouton 'Supprimer le commentaire' et place
 * dans le formulaire l'id. du commentaire.
 * @param   $res    mixed    Ligne récupérée dans la BDD.
 *                          Sert pour l'id. du commentaire.
 */
function affiche_btn_supprimer($res)
{
    global $idArticleCrypte;

    echo "<form action=\"article.php?id=$idArticleCrypte#comments\" method=\"post\">",
        '<input type="hidden" name="commentaire" value=' . $res['coID'] . '>',
        '<input type="submit" value="Supprimer le commentaire" name="btnSupprimer">',
        '</form>';
}

/**
 * Affiche la zone de texte pour les commentaires.
 */
function affiche_poster_comments()
{
    global $idArticleCrypte;

    echo '<form id="comments" action="article.php?id=', $idArticleCrypte, '#comments" method="post">',
        '<fieldset>',
        '<legend>Ajoutez un commentaire</legend>',
        '<textarea name="txtCommentaire"></textarea>',
        '<input type="hidden" name="idArticle" value="',
        $idArticleCrypte,
        '">',
        '<p><input type="submit" name="btnPublier" value="Publier ce commentaire"></p>',
        '</fieldset>',
        '</form>';
}

/**
 * Invite l'utilisateur à se connecter.
 */
function affiche_connexion()
{
    echo '<p>',
        '<a href="connexion.php">Connectez-vous</a> ou <a href="inscription.php">inscrivez-vous</a> ',
        'pour pouvoir commenter cet article !',
        '</p>';
}

/**
 * Supprimer de la bdd le commentaire identifié
 * grace au champ 'commentaire' du formulaire posté.
 */
function supprimer_comment_bd()
{
    global $co;

    $sql =  "DELETE FROM `commentaire`
            WHERE coID = '{$_POST['commentaire']}'";
    $res = $co->query($sql) or bd_erreur($co, $sql);
}

/**
 * Inscrit le commentaire dans la base de donnée.
 */
function publier_comment_bd()
{
    global $co, $idArticle;

    $sql_dernier_id = 'SELECT MAX(coID) as dernierID FROM `commentaire`';
    $res = $co->query($sql_dernier_id) or bd_erreur($co, $sql_dernier_id);
    $id = (int) $res->fetch_assoc()['dernierID'] + 1;

    $texte = proteger_entree($co, $_POST['txtCommentaire']);
    $date = date('YmdHs');

    $sql =  'INSERT INTO `commentaire` (coID, coAuteur, coTexte, coDate, coArticle) ' .
        "VALUES ($id, '{$_SESSION['pseudo']}', '$texte', '$date', $idArticle)";

    $res = $co->query($sql) or bd_erreur($co, $sql);
}
