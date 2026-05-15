<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        $sucursales = [
            ['nombre' => 'Sucursal 1',       'slug' => 'sucursal-1'],
            ['nombre' => 'Punto de Venta 1', 'slug' => 'pdv-1'],
            ['nombre' => 'Punto de Venta 2', 'slug' => 'pdv-2'],
        ];

        foreach ($sucursales as $s) {
            $this->crearSucursal($s['nombre'], $s['slug']);
        }
    }

    private function crearSucursal(string $nombre, string $slug): void
    {
        $this->command->info("Creando sucursal: {$nombre}");

        $sucursal = Sucursal::firstOrCreate(
            ['slug' => $slug],
            ['nombre' => $nombre, 'activa' => true]
        );

        // Admin user para cada sucursal
        if (!User::where('sucursal_id', $sucursal->id)->exists()) {
            User::create([
                'name'        => 'Admin ' . $nombre,
                'email'       => $slug . '@elixir.com',
                'password'    => Hash::make('password'),
                'rol'         => 'admin',
                'sucursal_id' => $sucursal->id,
            ]);
        }

        $catMap = $this->seedCategorias($sucursal->id);
        $this->seedProductos($sucursal->id, $catMap);

        $this->command->info("  ✓ {$nombre}: " . DB::table('productos')->where('sucursal_id', $sucursal->id)->count() . " productos");
    }

    private function seedCategorias(int $sucursalId): array
    {
        $nombres = [
            'AGUA', 'ALCOHOL', 'CERVEZA', 'CIGARRILLO', 'COCA',
            'ENERGIZANTE', 'GASEOSAS', 'LICORES', 'RON', 'SINGANI',
            'TEQUILA', 'VODKA', 'VINO', 'WHISKY',
        ];

        $map = [];
        $now = now();

        foreach ($nombres as $n) {
            $existing = DB::table('categorias')
                ->where('sucursal_id', $sucursalId)
                ->where('nombre', $n)
                ->first();

            if ($existing) {
                $map[$n] = $existing->id;
            } else {
                $map[$n] = DB::table('categorias')->insertGetId([
                    'sucursal_id' => $sucursalId,
                    'nombre'      => $n,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        return $map;
    }

    private function seedProductos(int $sucursalId, array $catMap): void
    {
        if (DB::table('productos')->where('sucursal_id', $sucursalId)->count() > 0) {
            return;
        }

        $now = now();

        // [codigo, nombre, compra, venta, mayoreo, categoria, min, max]
        $productos = [
            ['1007084702751', 'MONSTER ENERGY THE DOCTOR 473ML',            13.00,    15.00,   15.00, 'ENERGIZANTE',  5, 100],
            ['1007084789387', 'MONSTER ENERGY ULTRA 473ML',                 13.00,    15.00,   15.00, 'ENERGIZANTE',  5, 100],
            ['26964237689',   'RON FLOR DE CAÑA NORMAL CAFE 1L',            65.00,    80.00,    0.00, 'RON',          4, 100],
            ['26964453188',   'RON FLOR DE CAÑA EXTRA SECO 1L',             60.00,    80.00,    0.00, 'RON',          3, 100],
            ['40329055',      'CIGARRO CAMEL AMARILLO CAJ. 20UND',          17.00,    20.00,    0.00, 'CIGARRILLO',   2, 100],
            ['40329659',      'CIGARRILLO CAMEL AMARILLO CAJ. 10UND',        9.00,    10.00,   10.00, 'CIGARRILLO',   5, 100],
            ['4067700015327', 'JAGERMEISTER 700ML',                         92.00,   110.00,    0.00, 'LICORES',      3, 100],
            ['4067700017222', 'JAGERMEISTER 1L',                           110.00,   135.00,    0.00, 'LICORES',      2, 100],
            ['42137511',      'CIGARRILLO CAMEL AZUL CAJ. 10UND',            9.00,    10.00,   10.00, 'CIGARRILLO',   5, 100],
            ['5000267013602', 'JOHNNIE WALKER RED LABEL ORIGINAL 1L',      130.00,   150.00,    0.00, 'WHISKY',       3, 100],
            ['5000267013626', 'JOHNNIE WALKER RED LABEL',                  108.33,   120.00,  120.00, 'WHISKY',       3, 100],
            ['5000267023625', 'JOHNNIE WALKER BLACK LABEL 1L',             200.00,   240.00,    0.00, 'WHISKY',       3, 100],
            ['5000267114279', 'JOHNNIE WALKER BLUE LABEL 750ML',          1300.00,  1600.00,    0.00, 'WHISKY',       1,  10],
            ['5000281004020', 'WHISKY OLD PARR 12 AÑOS 1L',               180.00,   220.00,    0.00, 'WHISKY',       2, 100],
            ['5010327208107', 'WHISKY GRANTS TRIPLE WOOD 200ML',            25.00,    30.00,    0.00, 'WHISKY',       5, 100],
            ['5010327223209', 'WHISKY GRANTS TRIPLE WOOD 1L',               80.00,    90.00,    0.00, 'WHISKY',       3, 100],
            ['5099873006368', 'WHISKY JACK DANIELS JENNESSEE FIRE 1L',     160.00,   200.00,    0.00, 'WHISKY',       1, 100],
            ['6001495062508', 'AMARULA 750ML',                              90.00,   110.00,    0.00, 'LICORES',      4, 100],
            ['721059010009',  'VODKA SKYY EST 1992 1L',                     80.00,   100.00,    0.00, 'VODKA',        3, 100],
            ['7501008610121', 'RON SOLERA BACARDI 750ML',                   90.00,   110.00,    0.00, 'RON',          1, 100],
            ['7501021540054', 'TEQUILA GUSANO ROJO 750ML',                 160.00,   180.00,    0.00, 'TEQUILA',      3, 100],
            ['7501035011526', 'TEQUILA JOSE CUERVO ESPECIAL 750ML',         90.00,   110.00,    0.00, 'TEQUILA',      4, 100],
            ['7501035011533', 'TEQUILA JOSE CUERVO PLATA 750ML',            90.00,   110.00,    0.00, 'TEQUILA',      3, 100],
            ['75032715',      'CERVEZA CORONA 355ML',                        9.00,    10.00,    0.00, 'CERVEZA',     24, 100],
            ['7594003626631', 'RON DIPLOMATICO MANTUANO EXTRA AÑEJO 750ML',120.00,   150.00,    0.00, 'RON',          1, 100],
            ['7702090039214', 'SPEED MAX 269ML',                             8.00,    10.00,   10.00, 'ENERGIZANTE',  6, 100],
            ['7771224003018', 'TAMPICO CITRUS PUNCH 3L',                     8.00,    14.00,   14.00, 'CERVEZA',      3, 100],
            ['7771501000013', 'ALCOHOL GUABIRA 1L',                         13.75,    20.00,   20.00, 'ALCOHOL',      6, 100],
            ['7771605000032', 'FANTA NARANJA 2L',                           10.00,    12.00,   11.00, 'GASEOSAS',     2, 100],
            ['7771605000049', 'SPRITE 2L',                                  10.00,    11.00,    0.00, 'GASEOSAS',     5, 100],
            ['7771605000063', 'SIMBA PIÑA 2L',                               8.00,    10.00,    0.00, 'GASEOSAS',     1, 100],
            ['7771605000124', 'FANTA NARANJA 500ML',                         4.00,     6.00,    0.00, 'GASEOSAS',     2, 100],
            ['7771605000469', 'SIMBA MANZANA 2L',                            8.00,    10.00,    0.00, 'GASEOSAS',     2, 100],
            ['77716064',      'COCA COLA 500ML',                             4.00,     6.00,    0.00, 'GASEOSAS',     4, 100],
            ['77716088',      'COCA COLA 2L',                               10.00,    11.00,    0.00, 'GASEOSAS',     5, 100],
            ['7771609000052', 'AGUA VITAL 2L',                               6.00,     8.00,    0.00, 'AGUA',         3, 100],
            ['7771609000250', 'AGUA VITAL 500ML',                            4.00,     5.00,    0.00, 'AGUA',         3, 100],
            ['7771609000496', 'POWER ADE MORA AZUL 473ML',                   5.00,     6.00,    0.00, 'AGUA',         2, 100],
            ['7771609000526', 'POWER ADE MULTIFRUTAS 473ML',                 5.00,     6.00,    0.00, 'AGUA',         2, 100],
            ['7771609000656', 'AQUARIUS PERA 2L',                           10.00,    11.00,   11.00, 'AGUA',         4, 100],
            ['7771609000854', 'FANTA PAPAYA 2L',                             9.80,    11.00,   11.00, 'GASEOSAS',     5, 100],
            ['7771609000960', 'COCA COLA 3L',                               14.00,    15.00,    0.00, 'GASEOSAS',     6, 100],
            ['7771609001448', 'COCA COLA 300ML',                             2.50,     3.50,    0.00, 'GASEOSAS',     2, 100],
            ['7771609001455', 'FANTA NARANJA 300ML',                         2.50,     3.50,    0.00, 'GASEOSAS',     2, 100],
            ['7771609001462', 'SPRITE 300ML',                                2.50,     3.50,    0.00, 'GASEOSAS',     2, 100],
            ['7771609001622', 'SPRITE 3L',                                  14.00,    15.00,    0.00, 'GASEOSAS',     6, 100],
            ['7771609001646', 'FANTA NARANJA 3L',                           14.00,    15.00,    0.00, 'GASEOSAS',     2, 100],
            ['7771609001677', 'AGUA VITAL 3L',                               7.00,    10.00,    0.00, 'AGUA',         1, 100],
            ['7771609003251', 'AGUA VITAL 990ML',                            5.00,     6.00,    0.00, 'AGUA',         2, 100],
            ['7771609003268', 'POWER ADE MORA AZUL 990ML',                   9.00,    10.00,    0.00, 'AGUA',         3, 100],
            ['7771609003275', 'POWER ADE MULTIFRUTAS 990ML',                 9.00,    10.00,    0.00, 'AGUA',         3, 100],
            ['7771609003947', 'DEL VALLE MANZANA',                          15.00,    17.00,    0.00, 'GASEOSAS',     1, 100],
            ['7771612000001', 'SINGANI CASA REAL NEGRO 750ML',              60.00,    75.00,    0.00, 'SINGANI',      4, 100],
            ['7771612000025', 'SINGANI CASA REAL NEGRO 375ML',              45.00,    55.00,    0.00, 'SINGANI',      4, 100],
            ['7771612000032', 'SINGANI CASA REAL ROJO 750ML',               45.00,    55.00,    0.00, 'SINGANI',      4, 100],
            ['7771612000049', 'SINGANI CASA REAL ROJO 375ML',               30.00,    39.00,    0.00, 'SINGANI',      4, 100],
            ['7771612000056', 'SINGANI CASA REAL AZUL 750ML',               27.00,    35.00,    0.00, 'SINGANI',      6, 100],
            ['7771612000643', 'SINGANI CASA REAL AZUL 375ML',               20.00,    25.00,    0.00, 'SINGANI',      4, 100],
            ['7771621030099', 'RON REBEL 750ML',                            40.00,    55.00,    0.00, 'RON',          1, 100],
            ['7771621030136', 'VODKA POMELO REBEL 750ML',                   30.00,    45.00,    0.00, 'VODKA',        1, 100],
            ['7771621030143', 'VODKA REBEL MARACUYA 750ML',                 30.00,    45.00,    0.00, 'VODKA',        2, 100],
            ['7772102000006', 'SINGANI SAN PEDRO DE ORO 750ML',             60.00,    75.00,    0.00, 'SINGANI',      3, 100],
            ['7772106001467', 'PEPSI COLA 1L',                               5.00,     6.00,    6.00, 'GASEOSAS',     5, 1000],
            ['7772106002877', 'PEPSI COLA 3L',                              11.00,    12.00,    0.00, 'GASEOSAS',     5, 100],
            ['7772106002884', '7 UP 3L',                                    10.83,    12.00,   12.00, 'GASEOSAS',     5, 100],
            ['7772106002990', 'CERVEZA BOCK 440ML',                          9.00,    10.00,    0.00, 'CERVEZA',     12, 100],
            ['7772106007025', 'CERVEZA HUARI 235ML',                         4.00,     5.00,    0.00, 'CERVEZA',     96, 100],
            ['7772106007469', 'CERVEZA PACEÑA 269ML',                        4.67,     6.00,    6.00, 'CERVEZA',     40, 1000],
            ['7772106008183', 'CERVEZA HUARI LATA 310ML',                    8.00,    10.00,    0.00, 'CERVEZA',     12, 100],
            ['7772106008688', 'ROCKSTAR 300ML',                              3.50,     4.00,    4.00, 'ENERGIZANTE', 12, 100],
            ['7772106008909', 'CERVEZA GOLDEN LATA 269ML',                   5.00,     6.00,    0.00, 'CERVEZA',     20, 1000],
            ['7772106009043', 'CERVEZA HUARI LAGER BOTELLA 300ML',           8.00,    10.00,    0.00, 'CERVEZA',     12, 100],
            ['7772106009050', 'CERVEZA HUARI MIEL BOTELLA 300ML',            8.00,    11.00,    0.00, 'CERVEZA',      6, 100],
            ['7772109000023', 'SINGANI LOS PARRALES DOBLE ORO 750ML',       60.00,    75.00,    0.00, 'SINGANI',      2, 100],
            ['7772109000108', 'VINO ESPUMANTE ALTOSAMA DEMI SEC 750ML',     40.00,    50.00,    0.00, 'VINO',         2, 100],
            ['7772109000115', 'VINO ESPUMANTE ALTOSAMA ROSE 750ML',         40.00,    50.00,    0.00, 'VINO',         2, 100],
            ['7772109000122', 'VINO ESPUMANTE ALTOSAMA BRUT 750ML',         40.00,    50.00,    0.00, 'VINO',         2, 100],
            ['7772109000146', 'VINO BLANCO SANTO PATRONO 700ML',            22.00,    30.00,    0.00, 'VINO',         1, 100],
            ['7772109000207', 'VINO SANTO PATRONO COPLA MOSCATEL 750ML',    40.00,    50.00,    0.00, 'VINO',         2, 100],
            ['7772110000425', 'VINO BLANCO STIRPE 700CC',                   18.00,    25.00,    0.00, 'VINO',         2, 100],
            ['7772110000784', 'SINGANI RUJERO CLASICO NEGRO 750ML',         60.00,    75.00,    0.00, 'SINGANI',      2, 100],
            ['7772110000913', 'SINGANI RUJERO AZUL 1000ML',                 30.00,    40.00,    0.00, 'SINGANI',      5, 100],
            ['7772110000920', 'SINGANI RUJERO AZUL 750ML',                  25.00,    35.00,    0.00, 'SINGANI',      4, 100],
            ['7772110000944', 'SINGANI RUJERO MANGO NEGRO 750ML',           60.00,    75.00,    0.00, 'SINGANI',      1, 100],
            ['7772110001095', 'VINO LA CONCEPCION CEPAS DE ALTURA SIRAH',  160.00,   180.00,    0.00, 'VINO',         1, 100],
            ['7772112000003', 'VINO BLANCO CLASICO CAMPOS DE SOLANA 700ML', 22.00,    30.00,    0.00, 'VINO',         2, 100],
            ['7772112000010', 'VINO TINTO CLASICO CAMPOS DE SOLANA 700ML',  22.00,    30.00,    0.00, 'VINO',         2, 100],
            ['7772115000024', 'CUBA LIBRE 2L',                              22.00,    25.00,   25.00, 'RON',          4, 100],
            ['7772115000123', 'CUBA LIBRE BOT 500ML',                        9.00,    10.00,   10.00, 'RON',         12, 100],
            ['7772115000321', 'CUBA LIBRE LATA 355ML',                       8.00,    10.00,    0.00, 'LICORES',     96, 100],
            ['7772115100021', 'VINO BLANCO TERRUÑO ARANJUEZ 700ML',         18.00,    25.00,    0.00, 'VINO',         2, 100],
            ['7772115100038', 'VINO TINTO TERRUÑO ARANJUEZ 700ML',          18.00,    25.00,    0.00, 'VINO',         2, 100],
            ['7772115100137', 'VINO TERRUÑO ARANJUEZ OPORTO 700ML',         18.00,    25.00,    0.00, 'VINO',         3, 100],
            ['7772115390859', 'VODKA STARK',                                  8.00,    10.00,   10.00, 'VODKA',        3, 100],
            ['7772115420044', 'MALTA REAL LATA 350ML',                       3.87,     5.00,    0.00, 'GASEOSAS',    20, 1000],
            ['7772115420150', 'CERVEZA CAPITAL 350ML',                       4.08,     5.00,    5.00, 'CERVEZA',     48, 1000],
            ['7772115700153', 'VINO OPORTO VICTORIA 720ML',                 12.00,    15.00,    0.00, 'VINO',         2, 100],
            ['7772116030112', 'CERVEZA PACEÑA 440ML',                        6.91,    10.00,   10.00, 'CERVEZA',     48, 1000],
            ['7772116030389', 'CERVEZA TAQUIÑA CHICHA LATA 330ML',           4.00,     5.00,    0.00, 'CERVEZA',     98, 1000],
            ['7772116030396', 'CERVEZA TAQUIÑA CHICHA LATA 440ML',           5.00,     6.00,    0.00, 'CERVEZA',     12, 100],
            ['7772116030587', 'CERVEZA HUARI LATA 440ML',                    8.33,    11.00,    0.00, 'CERVEZA',     24, 100],
            ['7772116030723', 'MALTIN 440ML',                                4.50,     5.00,    0.00, 'ENERGIZANTE', 12, 100],
            ['7773800000329', 'CIGARRILLO DERBY CLIC CITRUSMINT CAJ 20UND',  9.72,    12.00,   12.00, 'CIGARRILLO',   1, 100],
            ['77738028',      'CIGARRILLO LM AZUL CAJ 20UND',                9.20,    11.00,   11.00, 'CIGARRILLO',   2, 100],
            ['77738127',      'CIGARRILLO CASINO CAJ 20UND',                 5.84,     7.00,    7.00, 'CIGARRILLO',   3, 100],
            ['77738172',      'CIGARRILLO DERBY RED CAJ 20UND',              9.22,    11.00,    0.00, 'CIGARRILLO',   2, 100],
            ['77738332',      'CIGARRILLO LM MENTA CAJ 20UND',               9.20,    12.00,   12.00, 'CIGARRILLO',   2, 100],
            ['7774904288941', 'FOURLOKO PONCHE DE FRUTAS 473ML',            23.00,    25.00,   25.00, 'LICORES',      3, 100],
            ['7774904288972', 'FOUR LOKO SANDIA 473ML',                     25.00,    25.00,   25.00, 'LICORES',      3, 100],
            ['7774904380027', 'FOURLOKO BLACK 695ML',                       33.00,    35.00,   35.00, 'LICORES',      3, 100],
            ['7774904400671', 'FOURLOKO BLUE 473ML',                        23.00,    25.00,   25.00, 'LICORES',      2, 100],
            ['7775021790089', 'CIGARRILLO MONTECARLO CAJ 10UND',             2.00,     3.00,    0.00, 'CIGARRILLO',   3, 100],
            ['77765987',      'CERVEZA HUARI 620ML UND',                    13.25,    15.00,   15.00, 'CERVEZA',     36, 1000],
            ['77768759',      'CIGARRILLO MONTECARLO CAJ 20UND',             4.00,     5.00,    0.00, 'CIGARRILLO',   3, 1000],
            ['77769060',      'CIGARRILLO CAMEL 2CLIC CAJ. 20UND',          17.02,    22.00,   22.00, 'CIGARRILLO',   3, 100],
            ['77769121',      'CIGARRILLO LD CAJ 20UND',                     8.00,    12.00,    0.00, 'CIGARRILLO',   5, 100],
            ['77769138',      'CIGARRILLO LD AZUL CAJ 20UND',                8.00,    10.00,   10.00, 'CIGARRILLO',   1, 100],
            ['77769145',      'CIGARRILLO LD ROJO CAJ 20UND',                8.00,    10.00,   10.00, 'CIGARRILLO',   2, 100],
            ['77769220',      'CIGARRILLO LD CLIC CAJ 14UNID',               6.00,    10.00,    0.00, 'CIGARRILLO',   5, 100],
            ['77769541',      'CIGARRILLO MARLBORO 3CLIC CAJ 20UND',        15.80,    20.00,   20.00, 'CIGARRILLO',   2, 100],
            ['77769671',      'CIGARRILLO DERBY MENTA CAJ 20UND',            9.72,    12.00,   12.00, 'CIGARRILLO',   2, 100],
            ['77770271',      'CIGARRILLO WINSTON CAJ 20UND',                7.80,    10.00,   10.00, 'CIGARRILLO',   3, 100],
            ['7790260013058', 'TRES PLUMAS HUEVO 700ML',                    20.00,    30.00,    0.00, 'LICORES',      1, 100],
            ['7790260013096', 'TRES PLUMAS CACAO 700ML',                    20.00,    30.00,    0.00, 'LICORES',      2, 100],
            ['7790260013157', 'TRES PLUMAS MENTA 700ML',                    20.00,    30.00,    0.00, 'LICORES',      5, 100],
            ['7790260013324', 'TRES PLUMAS MELON 750ML',                    20.00,    30.00,    0.00, 'LICORES',      2, 100],
            ['7790260016011', 'TRES PLUMAS LICOR SECO 930ML',               20.00,    35.00,    0.00, 'LICORES',      5, 100],
            ['7790290101473', 'FERNET BRANCA MENTA 75CL',                   47.50,    75.00,    0.00, 'LICORES',      3, 100],
            ['7790290101602', 'FERNET BRANCA 75CL',                         55.00,    75.00,    0.00, 'LICORES',      4, 100],
            ['7790314005893', 'VINO TORO VIEJO BONARDA SIRAH 3L',           45.00,    55.00,    0.00, 'VINO',         2, 100],
            ['7790314006760', 'VINO TORO BONARDA SIRAH BOTELLA 1125ML',     15.00,    25.00,    0.00, 'VINO',         5, 100],
            ['7790314063824', 'VINO TORO TINTO 1L',                         10.00,    15.00,    0.00, 'VINO',         6, 100],
            ['7790314063848', 'VINO TORO BLANCO 1L',                        10.00,    15.00,    0.00, 'VINO',         5, 100],
            ['7790950133691', 'DR LEMON MOJITO BOTELLA 1L',                 15.00,    30.00,    0.00, 'LICORES',      3, 100],
            ['7790950144550', 'DR LEMON BLACK CHERRY 473ML',                 8.00,    10.00,    0.00, 'LICORES',     12, 1000],
            ['7791540053153', 'FRIZZE BLUE 473ML',                           6.67,    10.00,    0.00, 'CERVEZA',     48, 1000],
            ['7791540053184', 'FRIZZE BLUE EVOLUTION 1L',                   15.00,    35.00,    0.00, 'LICORES',      2, 100],
            ['7791843008294', 'VINO VIÑAS DEL BALBO CLASICO TINTO',         30.00,    35.00,    0.00, 'VINO',         2, 100],
            ['7792410527880', 'WHISKY DOBLE V 1L',                          25.00,    35.00,    0.00, 'WHISKY',       5, 100],
            ['7792410528887', 'FERNET CAPRI 70CL',                          16.00,    35.00,    0.00, 'LICORES',     12, 100],
            ['7792798008377', 'CERVEZA CORONA LATA 269ML',                   5.00,     8.00,    0.00, 'CERVEZA',     20, 1000],
            ['7793147118860', 'CERVEZA SCHNEIDER 473ML',                     7.29,     9.00,    0.00, 'CERVEZA',     96, 1000],
            ['7798107414471', 'TRIP BAR MOJITO 750ML',                      15.00,    35.00,    0.00, 'LICORES',      2, 100],
            ['7798135763091', 'VODKA NEW STYLE MANZANA VERDE 1L',           23.50,    35.00,    0.00, 'VODKA',        2, 100],
            ['7798167047923', 'FERNET COLA 1882 473ML',                      8.76,    10.00,    0.00, 'LICORES',     48, 1000],
            ['7798422620014', 'MONSTER ENERGY BLACK 473ML',                 13.00,    15.00,    0.00, 'ENERGIZANTE',  5, 100],
            ['78415690',      'CIGARRILLO GIF MENTOL CAJ 20UND',             5.00,    10.00,   10.00, 'CIGARRILLO',   1, 100],
            ['7896008113407', 'VODKA BARKOV FRUTOS ROJOS 200ML',             6.00,    10.00,    0.00, 'VODKA',        5, 100],
            ['7896008113445', 'WHISKY OLD RED 200ML',                        7.00,    10.00,    0.00, 'WHISKY',       4, 100],
            ['7896072901139', 'WHISKY CHANCELER GOLDEN LABEL 1L',           35.00,    45.00,    0.00, 'WHISKY',       5, 100],
            ['7898295301352', 'CERVEZA BURGUESA LATA 350ML',                 7.00,     8.00,    0.00, 'CERVEZA',     12, 100],
            ['7898295301437', 'CERVEZA BURGUESA 269ML',                      5.00,     6.00,    0.00, 'CERVEZA',     12, 100],
            ['7898295301505', 'CERVEZA BURGUESA LATA 473ML',                 9.00,    10.00,    0.00, 'CERVEZA',     12, 100],
            ['80432400432',   'WHISKY CHIVAS REGAL 12 AÑOS',               180.00,   220.00,    0.00, 'WHISKY',       1, 100],
            ['80480985462',   'MOJITO BACARDI 750ML',                       60.00,    75.00,    0.00, 'LICORES',      2, 100],
            ['821490100809',  'CIGARRILLO 1020 CAJ 20UND',                   5.00,     6.00,    0.00, 'CIGARRILLO',   4,  50],
            ['82184090466',   'WHISKY JACK DANIELS OLD NO7 BRAND',         150.00,   200.00,    0.00, 'WHISKY',       2, 100],
            ['8501110080439', 'RON HABANA CLUB 7AÑOS 70CL',               117.00,   130.00,    0.00, 'RON',          3, 100],
            ['88291100036',   'RON ABUELO 200ML SOBAQUERA',                 20.00,    30.00,    0.00, 'RON',          3, 100],
            ['88291100067',   'RON ABUELO 1.75L PATA ELEFANTE',           130.00,   150.00,    0.00, 'RON',          2, 100],
            ['88291100302',   'RON ABUELO 1L',                              80.00,    90.00,    0.00, 'RON',          6, 100],
            ['8906006541574', 'CIGARRILLO BLUEMOUNT CAJ 20UND',              8.00,    10.00,   10.00, 'CIGARRILLO',   2, 100],
            ['895216001046',  'FOURLOKO PONCHE DE FRUTAS 695ML',            33.00,    35.00,   35.00, 'LICORES',      2, 100],
            ['895216001077',  'FOURLOKO SANDIA 695ML',                      33.00,    35.00,   35.00, 'LICORES',      3, 100],
            ['9002490100070', 'RED BULL 250ML',                              8.00,    12.00,   12.00, 'ENERGIZANTE',  5, 100],
            ['PROD-003',      'CIGARRILLO DERBY SUAVE UNID',                 0.45,     0.50,    0.50, 'CIGARRILLO',  60, 100],
            ['PROD-004',      'CIGARRILLO LM CLIC UNID',                     0.62,     1.00,    0.00, 'CIGARRILLO',  80, 1000],
            ['PROD-005',      'CIGARRILLO LM CANELA UND',                    0.66,     1.00,    1.00, 'CIGARRILLO',  80, 1000],
            ['PROD-006',      'CIGARRILLO LM SANDIA UND',                    0.45,     1.00,    1.00, 'CIGARRILLO',  50, 1000],
            ['PROD-007',      'CIGARRILLO LM 2CLIC UND',                     0.70,     1.50,    1.50, 'CIGARRILLO',  50, 1000],
            ['PROD-008',      'CIGARRILLO DERBY AMARILLO UND',               0.33,     0.50,    0.50, 'CIGARRILLO',  80, 1000],
            ['PROD-012',      'ALCOHOL PEQUEÑO',                             2.00,     3.00,    3.00, 'ALCOHOL',     12, 100],
            ['PROD-013',      'ALCOHOL MEDIANO',                             4.00,     5.00,    5.00, 'ALCOHOL',     12, 100],
            ['PROD-014',      'ALCOHOL GRANDE',                              9.00,    10.00,   10.00, 'ALCOHOL',     24, 100],
            ['PROD-015',      'SINGANI GRANADA PEQUEÑO 250ML',               4.00,     5.00,    5.00, 'SINGANI',     24, 100],
            ['PROD-016',      'SINGANI GRANADA MEDIANO 500ML',               8.00,    10.00,   10.00, 'SINGANI',     12, 1000],
            ['PROD-017',      'SINGANI GRANADA GRANDE 1L',                  15.00,    20.00,   20.00, 'SINGANI',      6, 100],
            ['PROD-018',      'RON MATUCA 500ML',                            6.00,    10.00,   10.00, 'RON',          3, 100],
            ['PROD-019',      'SINGANI GALON 5L',                           40.00,    50.00,   50.00, 'SINGANI',      3, 100],
            ['PROD-020',      'VODKA GALON 5L',                             45.00,    50.00,   50.00, 'VODKA',        3, 100],
            ['PROD-021',      'COCA MACHUCADA BENITA',                      14.00,    15.00,   15.00, 'COCA',        15, 100],
            ['PROD-022',      'MATADIABLO 2L',                               6.83,    10.00,   10.00, 'LICORES',     30, 100],
            ['PROD-024',      'CAPRICE 2L',                                  6.33,    10.00,   10.00, 'LICORES',     20, 1000],
            ['PROD-026',      'LICOR 43 DE 2L',                              7.16,    10.00,   10.00, 'LICORES',     20, 1000],
        ];

        $rows = [];
        foreach ($productos as [$codigo, $nombre, $compra, $venta, $mayoreo, $cat, $min, $max]) {
            $rows[] = [
                'sucursal_id'    => $sucursalId,
                'codigo_barras'  => $codigo,
                'nombre'         => $nombre,
                'precio_compra'  => $compra,
                'precio_venta'   => $venta,
                'precio_mayoreo' => $mayoreo,
                'categoria_id'   => $catMap[$cat] ?? null,
                'stock_actual'   => 0,
                'stock_minimo'   => $min,
                'stock_maximo'   => $max,
                'activo'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('productos')->insert($chunk);
        }
    }
}
