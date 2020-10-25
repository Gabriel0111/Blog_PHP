<?php

/** Constantes : les paramètres de connexion au serveur MySQL */
define('BD_NAME', 'echo_bd');
define('BD_USER', 'echo_user');
define('BD_PASS', 'echo_passe');
define('BD_SERVER', 'localhost');


//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Cette fonction est (pour le devoir 1) appelée quand une tentative de piratage est détectée. 
 * 
 * @param string	URL de la page vers laquelle l'utilisateur est redirigé
 */
function fd_exit_session($page = '../index.php')
{
    header("Location: $page");
    exit();
}


//_______________________________________________________________
/**
 *	Affichage de l'entete HTML + entete de la page web (bandeau de titre + menu)
 *	@param 	String 	$title 	Le titre de la page (<head>)
 *	@param 	array	$css	Le chemin vers la feuille de style à inclure
 */
function fd_entete($title, $prefix = '..', $css = '')
{

    echo
        '<!doctype html>',
        '<html lang="fr">',
        '<head>',
        '<meta charset="UTF-8">',
        '<title>L\'écho de l\'EAD | ',
        $title,
        '</title>',
        '<link rel="stylesheet" type="text/css" href="',
        $css,
        '">',
        '</head>',
        '<body>';

    fd_menu($prefix);

    echo '<header>',
        '<img src="',
        $prefix,
        '/images/titre.png" alt="L\'écho de l\'EAD" width="685" height="83">',
        '<h1>',
        $title,
        '</h1>',
        '</header>';
}

//_______________________________________________________________
/**
 *  Génère le code du menu de navigation. 
 *  @param  String  $prefix     le préfix du chemin relatif vers la racine du site 
 */
function fd_menu($prefix)
{
    $pseudo = (isset($_SESSION['pseudo']) ? $_SESSION['pseudo'] : NULL);
    echo '<nav><ul>',
        '<li><a href="',
        $prefix,
        '/index.php">Accueil</a></li>',
        '<li><a href="',
        $prefix,
        '/php/actus.php">Toute l\'actu</a></li>',
        '<li><a href="',
        $prefix,
        '/php/recherche.php">Recherche</a></li>',
        '<li><a href="',
        $prefix,
        '/php/redaction.php">La rédac\'</a></li>';

    if ($pseudo !== NULL) {
        $isAdmin = $_SESSION['estRedacteur'];
        echo "<li><a href='#'>$pseudo</a><ul>",
            '<li><a href="',
            $prefix,
            '/php/compte.php">Mon profil</a></li>';
        echo ($isAdmin) ? '<li><a href="' . $prefix . '/php/nouveau.php">Nouvel article</a></li>' : '',
            '<li><a href="',
            $prefix,
            '/php/deconnexion.php">Se déconnecter</a></li>';
    } else
        echo '<li><a href="', $prefix, '/php/connexion.php">Se connecter</a></li>';
    echo '</ul></li></nav>';
}


//_______________________________________________________________
/**
 *  Affichage du pied de page du document. 
 */
function fd_pied()
{
    $mois = date('F');
    switch ($mois) {
        case 'January':
            $mois = 'Janvier';
            break;
        case 'February':
            $mois = 'Février';
            break;
        case 'March':
            $mois = 'Mars';
            break;
        case 'April':
            $mois = 'Avril';
            break;
        case 'May':
            $mois = 'Mai';
            break;
        case 'June':
            $mois = 'Juin';
            break;
        case 'July':
            $mois = 'Juillet';
            break;
        case 'August':
            $mois = 'Août';
            break;
        case 'September':
            $mois = 'Septembre';
            break;
        case 'October':
            $mois = 'Octobre';
            break;
        case 'November':
            $mois = 'Novembre';
            break;
        case 'December':
            $mois = 'Décembre';
            break;
        default:
            $mois = '';
            break;
    }

    echo  '<footer>&copy; Master EAD Informatique - ', $mois, date(' Y'), ' - Tous droits réservés</footer>',
        '</body>',
        '</html>';
}



//_______________________________________________________________
/**
 *  Affichage d'une tableau d'articles sous forme de vignettes.
 *  @param  String  $titre  le titre de la <section>
 *  @param  array   $tab    le tableau des enregistrements à afficher (issus de la table "article")
 *  @param  String  $prefix le chemin relatif vers la racine du site
 */
function fd_afficher_vignettes($titre, $tab, $prefix = '..')
{

    echo '<section class="vignettes"><h2>', $titre, '</h2>';

    foreach ($tab as $value) {
        fd_afficher_une_vignette($value, $prefix);
    }

    echo '</section>';
}


//_______________________________________________________________
/**
 *  Affichage d'un article sous forme de vignette (image + titre de l'article)
 *  @param  array   $value  tableau associatif issu des enregistrements de la table "article"  
 *  @param  String  $prefix le chemin relatif vers la racine du site
 */
function fd_afficher_une_vignette($value, $prefix = '..')
{

    $value = proteger_sortie($value);
    $id = $value['arID'];

    echo '<article>',
        '<a href="',
        $prefix,
        '/php/article.php?id=',
        crypterURL($id),
        '">',
        '<img src="',
        fd_url_image_illustration($id, $prefix),
        '" alt="Photo d\'illustration | ',
        $value['arTitre'],
        '">',
        '<h3>',
        $value['arTitre'],
        '</h3>',
        '</a>',
        '</article>';
}

//_______________________________________________________________
/**
 *  Génère l'URL de l'image d'illustration d'un article, en fonction de son ID
 *  - si l'image existe dans le répertoire /upload, on l'utilise 
 *  - si l'article n'existe pas : image générique "image non disponible"
 *  @param  int     $id         l'identifiant de l'article
 *  @param  String  $prefix     le chemin relatif vers la racine du site
 */
function fd_url_image_illustration($id, $prefix = '..')
{
    $url = "{$prefix}/upload/{$id}.jpg";

    if (!file_exists($url)) {
        $url = "{$prefix}/images/none.jpg";
    }

    return $url;
}


//_______________________________________________________________
/** 
 *  Calcule le résultat d'une requête SQL et place ceux-ci dans un tableau. 
 *  @param  Object  $co     la connexion à la base de données
 *  @param  String  $sql    la requête SQL à considérer
 */
function fd_calcule_articles_pour_requete($co, $sql)
{

    // envoi de la requête au serveur de bases de données
    $res = $co->query($sql) or bd_erreur($co, $sql);

    // tableau de résultat (à remplir)
    $ret = array();

    // parcours des résultats
    while ($t = $res->fetch_assoc()) {
        $ret[$t['arID']] = $t;
    }

    $res->free();

    return $ret;
}


//_______________________________________________________________
/**
 *  Affchage d'un message d'erreur dans une zone dédiée de la page.
 *  @param  String  $msg    le message d'erreur à afficher.
 */
function fd_afficher_erreur($msg)
{
    echo '<main>',
        '<section>',
        '<h2>Oups, il y a une erreur...</h2>',
        '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
        '<blockquote>',
        $msg,
        '</blockquote>',
        '</section>',
        '</main>';
}


//_______________________________________________________________
/**
 *  Affichage d'une date format AAAAMMJJHHMM au format JJ mois AAAA à HH:MM
 *  @param  int     $date   la date à afficher. 
 */
function fd_affiche_date($date)
{
    // si un article a été publié avant l'an 1000, ça marche encore :-)
    $min = substr($date, -2);
    $heure = (int) substr($date, -4, 2); //conversion en int pour supprimer le 0 de '07' pax exemple
    $jour = (int) substr($date, -6, 2);
    $mois = substr($date, -8, 2);
    $annee = substr($date, 0, -8);

    $month = get_tableau_mois();

    return $jour . ' ' . mb_strtolower($month[$mois - 1], 'UTF-8') . ' ' . $annee . ' à ' . $heure . ':' . $min;
    // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strtolower.php
}


//_______________________________________________________________
/**
 *  Traitement du texte pour remplacer les pseudo-balises par des vraies balises HTML.
 *  @param  String  $texte      le texte à traiter
 */
function fd_traiter_texte($texte)
{

    // remplacement des balises communes (strong, em, blockquote)
    $balises = array('p' => 'p', 'gras' => 'strong', 'it' => 'em', 'citation' => 'blockquote', 'liste' => 'ul', 'item' => 'li');

    foreach ($balises as $ici => $la) {
        $texte = str_replace("[$ici]", "<{$la}>", $texte);
        $texte = str_replace("[/$ici]", "</{$la}>", $texte);
    }

    // remplacement des retours à la ligne 
    $texte = str_replace('[br]', '<br>', $texte);

    // remplacement des liens [a:url] --> <a href="">
    $texte = preg_replace_callback('/\[a\:[^\]]*\]/', function ($matches) {
        return '<a href="' . substr($matches[0], 3, strlen($matches[0]) - 4) . '">';
    }, $texte);
    $texte = str_replace('[/a]', '</a>', $texte);

    return $texte;
}


//_______________________________________________________________
/**
 *  Affichage du résumé des articles - utilisé pour les pages "Toute l'actu" et "Recherche",
 *  avec groupement dans les sections par mois de publication (indiqué dans le titre de section).
 *  @param  array   $res    le tableau des enregistrements à afficher.
 */
function fd_afficher_resume($res)
{

    $mois = get_tableau_mois();

    $last = 0;

    echo '<section>';

    foreach ($res as $t) {
        $t = proteger_sortie($t);
        $moisCourant = (int) substr($t['arDatePublication'], -8, 2);
        if ($last != $moisCourant) {
            if ($last != 0) {
                echo '</section><section>';
            }
            echo '<h2>', $mois[$moisCourant - 1], ' ',  (int) ($t['arDatePublication'] / 100000000), '</h2>';
            $last = $moisCourant;
        }
        echo '<article class="resume">',
            '<aside>',
            '<img src="',
            fd_url_image_illustration($t['arID'], '..'),
            '" alt="Photo d\'illustration | ',
            $t['arTitre'],
            '">',
            '</aside>',
            '<h3>',
            $t['arTitre'],
            '</h3>',
            '<p>',
            $t['arResume'],
            '</p>',
            '<footer><a href="article.php?id=',
            crypterURL($t['arID']),
            '">Lire l\'article</a></footer>',
            '</article>';
    }
    echo '</section>';
}
