# Skrip kerja F6-W5

Diselamatkan daripada scratchpad sesi (folder Temp Windows) supaya tidak hilang.

| Fail | Guna |
|---|---|
| `w5-map.mjs` | Inventori W5: petakan setiap langkah generik → route diisytihar → sasaran sedia ada. Terbitan `../../plan-f6-w4/skrip/w4-map.mjs` dengan `wave === 'W5'`. |

⚠️ Ia mengira `s.route ?? g.route`. Langkah yang TIDAK mengisytiharkan route sendiri akan
kelihatan berada pada route GUIDE — itulah yang menyebabkan `/delegasi`,
`/classification-nodes` dan `/retensi-peraturan` tersalah lapor sebagai "tiada sasaran"
sedangkan sasarannya ada pada sub-route `/create`. Semak `s.route` mentah sebelum
membuat kesimpulan.
