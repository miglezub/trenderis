<template>
    <p class="text-center">
        <table id="text-info-table" class="table table-striped table-responsive table-bordered text-left">
            <thead>
                <tr>
                    <th scope="col">GET</th>
                    <th colspan="2" scope="col">/portal/text/{id}?key={key}&limit={limit}</th>
                </tr>
                <tr>
                    <th scope="col">Parametras</th>
                    <th scope="col">Aprašymas</th>
                    <th scope="col">Pavyzdys</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>id</th>
                    <td>Privalomas. Išorinis teksto id, pagal kurį identifikuojamas ieškomas tekstas.</td>
                    <td><span class="text-primary">/1</span></td>
                </tr>
                <tr>
                    <th>key</th>
                    <td>Privalomas. API raktas sugeneruotas grafinėje vartotojo sąsajoje. Naudojamas identifikuoti vartotoją.</td>
                    <td><span class="text-primary">?key=123456</span></td>
                </tr>
                <tr>
                    <th>limit</th>
                    <td>Neprivalomas. Nurodo, kiek svarbiausių teksto raktažodžių grąžinti. Pagal nutylėjimą naudojamas limitas yra 20 raktažodžių.</td>
                    <td><span class="text-primary">&limit=5</span></td>
                </tr>
                <tr class="apiExample">
                    <th>GET</th>
                    <td colspan="2">
                        /portal/text/1?key=123456&limit=5
                    </td>
                </tr>
            </tbody>
        </table>
    </p>
</template>