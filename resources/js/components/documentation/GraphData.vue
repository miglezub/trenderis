<template>
    <p class="text-center">
        <table id="graph-data-table" class="table table-striped table-responsive table-bordered text-left">
            <thead>
                <tr>
                    <th scope="col">GET</th>
                    <th colspan="2" scope="col">/portal/graph?key={key}&type={type}&date1={date1}&date2={date2}&keyword={keyword}</th>
                </tr>
                <tr>
                    <th scope="col">Parametras</th>
                    <th scope="col">Aprašymas</th>
                    <th scope="col"  class="example">Pavyzdys</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>key</th>
                    <td>Privalomas. API raktas sugeneruotas grafinėje vartotojo sąsajoje. Naudojamas identifikuoti vartotoją.</td>
                    <td><span class="text-primary">?key=123456</span></td>
                </tr>
                <tr>
                    <th>type</th>
                    <td>Privalomas. Grafiko tipas, galimos reikšmės: 1 – populiariausių raktažodžių grafikas, 2 – raktažodžio istorinis grafikas.</td>
                    <td><span class="text-primary">&type=1</span></td>
                </tr>
                <tr>
                    <th>date1</th>
                    <td>Privalomas, jei pasirinktas 1 tipo grafikas. Data nuo kurios filtruojami tekstai.</td>
                    <td><span class="text-primary">&date1=2021-05-04</span></td>
                </tr>
                <tr>
                    <th>date2</th>
                    <td>Privalomas, jei pasirinktas 1 tipo grafikas. Data iki kurios filtruojami tekstai.</td>
                    <td><span class="text-primary">&date2=2021-05-04</span></td>
                </tr>
                <tr>
                    <th>keyword</th>
                    <td>Privalomas, jei pasirinktas 2 tipo grafikas. Raktažodis, kurio istorinis grafikas formuojamas.</td>
                    <td><span class="text-primary">&keyword=nausėda</span></td>
                </tr>
                <tr class="apiExample">
                    <th>GET</th>
                    <td colspan="2">
                        /portal/graph?key=123456&type=1&date1=2021-05-04&date2=2021-05-04
                    </td>
                </tr>
                <tr>
                    <th>GET</th>
                    <td colspan="2">
                        /portal/graph?key=123456&type=2&keyword=nausėda
                    </td>
                </tr>
            </tbody>
        </table>
    </p>
</template>