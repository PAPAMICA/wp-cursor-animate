=== Curseur animé Kart ===
Contributors: papamica
Tags: cursor, animation, kart, karting, mouse
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Remplace le curseur des visiteurs par un kart animé qui suit la direction du mouvement, avec un effet de fumée.

== Description ==

Curseur animé Kart transforme le pointeur de la souris de vos visiteurs en un petit kart de course. Le kart suit le curseur avec un mouvement fluide, s'oriente dans le sens du déplacement et laisse derrière lui un panache de fumée lorsque le curseur bouge.

Pensé au départ pour un site de karting, le plugin est extensible pour accueillir d'autres curseurs à l'avenir.

Fonctionnalités :

* Remplacement du curseur par un kart animé (image fournie).
* Rotation du kart en fonction de la direction du mouvement.
* Effet de fumée réglable (faible, moyenne, forte) ou désactivable.
* Page de réglages dédiée dans Réglages → Curseur animé.
* Activation ou désactivation globale.
* Ciblage par pages : toutes les pages, uniquement une sélection, ou toutes sauf une sélection.
* Image de curseur personnalisée via la médiathèque.
* Réglages de taille et de fluidité.

Comportements automatiques :

* Désactivé sur mobile et tablette (pas de pointeur souris).
* Jamais chargé dans l'administration.
* Respecte la préférence système « réduire les animations » (mouvement direct, sans fumée).

== Installation ==

1. Téléversez le dossier `wp-cursor-animate` dans `/wp-content/plugins/`.
2. Activez le plugin depuis le menu « Extensions » de WordPress.
3. Rendez-vous dans Réglages → Curseur animé pour configurer le curseur.

== Frequently Asked Questions ==

= Le curseur ne s'affiche pas sur mobile, est-ce normal ? =

Oui. Les appareils tactiles n'ont pas de pointeur souris, l'animation y est donc désactivée automatiquement.

= Puis-je utiliser ma propre image ? =

Oui, via le champ « Image personnalisée » de la page de réglages. Une image orientée vers la droite donnera la meilleure rotation.

== Changelog ==

= 1.0.0 =
* Version initiale : curseur kart, rotation directionnelle, fumée et page de réglages.
