<template>
    <p class="text-center">
        <table id="documentation-table" class="table table-striped table-responsive table-bordered text-left">
            <thead>
                <tr>
                    <th colspan="2" scope="col">POST</th>
                    <th colspan="2" scope="col">/portal/text</th>
                </tr>
                <tr>
                    <th colspan="2" scope="col">Parametras</th>
                    <th scope="col">Aprašymas</th>
                    <th scope="col">Pavyzdys</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th colspan="2">key</th>
                    <td>Privalomas. API raktas sugeneruotas grafinėje vartotojo sąsajoje. Naudojamas identifikuoti vartotoją.</td>
                    <td><span class="text-danger">"key"</span>: <span class="text-primary">"123456"</span></td>
                </tr>
                <tr>
                    <th colspan="2">language</th>
                    <td>Privalomas. Pridedamo teksto/tekstų kalbos id. Galimi variantai: 1 – lietuvių k., 2 – anglų k.</td>
                    <td><span class="text-danger">"language"</span>: <span class="text-primary">"1"</span></td>
                </tr>
                <tr>
                    <th colspan="2">callback</th>
                    <td><i>Callback</i> nuoroda, į kurią bus grąžinti analizės rezultatai, jei analizuojama daugiau nei 1 tekstas.</td>
                    <td><span class="text-danger">"callback"</span>: <span class="text-primary">"http://trenderis.local/portal/callback"</span></td>
                </tr>
                <tr>
                    <th rowspan="5">texts</th>
                    <td></td>
                    <td>Privalomas. Analizės tekstų masyvas. Turi atitikti struktūrą, aprašomą žemesnėse eilutėse.</td>
                    <td rowspan="5">
                        <span class="text-danger">"texts":</span><br>
                        [<br>
                        &nbsp;&nbsp;{<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"id"</span>: <span class="text-primary">1</span>,<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"description"</span>: <span class="text-primary">"Testinis pirmas tekstas."</span>,<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"title"</span>: <span class="text-primary">"Pirmas"</span>,<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"created_at"</span>: <span class="text-primary">"2021-05-01T19:00:00+03:00"</span><br>
                        &nbsp;&nbsp;},<br>
                        &nbsp;&nbsp;{<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"id"</span>: <span class="text-primary">2</span>,<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger">"description"</span>: <span class="text-primary">"Testinis antras tekstas."</span><br>
                        &nbsp;&nbsp;}<br>
                        ]<br>
                    </td>
                </tr>
                <tr>
                    <td>id</td>
                    <td>Privalomas. Išorinės sistemos teksto id, privalo būti unikalus. Pagal šį id bus galima gauti analizės rezultatus per API sąsają.</td>
                </tr>
                <tr>
                    <td>description</td>
                    <td>Privalomas. Tekstas, kuriam turi būti atliekama analizė.</td>
                </tr>
                <tr>
                    <td>title</td>
                    <td>Neprivalomas. Teksto pavadinimas.</td>
                </tr>
                <tr>
                    <td>created_at</td>
                    <td>Neprivalomas. Teksto sukūrimo data ir laikas. Jei šis parametras nenurodytas, tekstui priskiriama dabartinė data ir laikas.</td>
                </tr>
            </tbody>
        </table>
    </p>
</template>