# OpenClassrooms - Projet 7 - Créez un web service exposant une API

## Présentation

Dépôt Git de [BileMo](https://bilemo.jm-projets.fr/).

Ce projet est le septième projet de la formation Développeur d'application - PHP/Symfony d'OpenClassrooms.

**BileMo** est une entreprise offrant toute une sélection de téléphones mobiles haut de gamme.

Je suis en charge du développement de la vitrine de téléphones mobiles de l’entreprise BileMo.
Le business modèle de BileMo n’est pas de vendre directement ses produits sur le site web, mais de fournir à toutes les plateformes qui le souhaitent l’accès au catalogue via une **API** (Application Programming Interface).
Il s’agit donc de vente exclusivement en B2B (business to business).

Il va falloir que j'expose un certain nombre d’API pour que les applications des autres plateformes web puissent effectuer des opérations.

## Configuration conseillée

Le projet a été développé sur un serveur local avec les versions suivantes :

> - Apache 2.4.51
> - PHP 8.0.11
> - [MySQL](https://www.mysql.com/fr/) 5.7.42
> - [Composer](https://getcomposer.org/) 2.5.7
> - [Node.js](https://nodejs.org/en/) 14.21.3
> - [Yarn](https://yarnpkg.com/) 1.22.19

## Installation

- Cloner le dépôt Git

```bash
git clone git@github.com:jeremymls/BileMo.git
```

- Dans le dossier cloné (`BileMo`), copier le fichier **.env** et le renommer en **.env.local**

```bash
cd BileMo
cp .env .env.local
```

- Configurer les variables d'environnement dans le fichier **.env.local**

- Lancer le script d'installation

```bash
sh deploy.sh
```

## Utilisation

Une fois installé, consultez la documentation de l'API à l'adresse suivante :
[https://bilemo.jm-projets.fr/api/doc](https://bilemo.jm-projets.fr/api/doc)
