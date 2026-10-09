<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paiement des élections
    |--------------------------------------------------------------------------
    |
    | Quand ce réglage est à false, aucune page de paiement n'est accessible :
    | toute élection créée est active d'office, les liens de vote partent dès
    | la création et les votants peuvent voter sans qu'aucun paiement n'ait eu
    | lieu. Pour rétablir le paiement Afribapay, passer BILLING_ENABLED à "true"
    | dans docker-compose.yaml (service app) puis redéployer.
    |
    */

    'enabled' => filter_var(env('BILLING_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

];
