# Delivery app

Based on Laravel 5.8 and RideOn 2.2 code base

## Developing dependencies

- PHP > 7.8
- composer == 1.10.17
- node.js (npm) > 14.0.0
- laravel-mix > 6.0.0

## Installation

Configure MySQL instance and export `database.zip` dump to the database.

Configure `.env` file same way as in `.env.development` file.

Than run composer installation:

    php composer.phar install


## 3rd-party

The application use integrations with next services:

- Digital Ocean Spaces
- Google Maps
- HERE map services
- Gloria food
- Yelo
- SquareUP
- Cloudwaitress
- Twilio
- PayPal
- Stripe
- SendBird
- GrowSurf
- JotForm

## Frontend apps

The apllication itself is a couple of pages or frontend applications. 

There are:

- Admin \ Dispatcher portal
- Driver portal
- Tracking portal
- Merchant portal
- Affiliate portal

Portals use classic route -> controller -> blade implementation with couple of js script and style assets.

Admin portal uses Yajra-datatables massively.

## Deployment

See `.gitlab-ci.yml` and Ansible scripts at `deployment` folder to get how to deploy application.

## Developer notes

This app negatively reacts to caching. Do not use `php artisan config:cache` or fix it.

Redisign frontend code using Vue and Laravel mix. Sample Vue app is on `/tracking-vue` route and uses TrackingPageController@index. To build Vue assets using mix, install npm package `npm install` and run `npm run prod`. Mix apps should be defined at `webpack.mix.js`.
