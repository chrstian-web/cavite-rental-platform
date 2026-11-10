<?php

/*
|--------------------------------------------------------------------------
| Owner subscription plans
|--------------------------------------------------------------------------
| `max_properties` is how many of an owner's properties tenants can see.
| `boost` is the search priority tier (0 = none; higher ranks first).
| Prices are in Philippine pesos per billing period.
*/
return [

    'period_days' => 30,

    'plans' => [
        'free' => ['name' => 'Free', 'price' => 0, 'max_properties' => 2, 'boost' => 0],
        'pro' => ['name' => 'Pro', 'price' => 500, 'max_properties' => 6, 'boost' => 1],
        'pro_plus' => ['name' => 'Pro+', 'price' => 1000, 'max_properties' => 13, 'boost' => 2],
        'vip' => ['name' => 'VIP', 'price' => 1500, 'max_properties' => 20, 'boost' => 3],
    ],
];
