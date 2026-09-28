<?php

return [
    ['query' => 'nescal 400g', 'expected' => 'Nescau 400 g', 'max_rank' => 1],
    ['query' => 'leit zero lac 1l', 'expected' => 'Leite zero lactose 1 L', 'max_rank' => 1],
    ['query' => 'coca lat', 'expected' => 'Coca-Cola em lata', 'max_rank' => 1],
    ['query' => 'arros 5kg', 'expected' => 'Arroz 5 kg', 'max_rank' => 1],
    ['query' => '7891000100103', 'expected' => 'Produto EAN exato', 'max_rank' => 1],
];
