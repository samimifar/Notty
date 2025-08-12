<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorldObservancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Wipe only the year-agnostic UN rows we manage (year 2000), keep user/other years intact
        DB::table('public_events')->whereYear('date', 2000)->delete();

        // Raw UN observances list pasted by user (title line followed by a date line like "04 Jan")
        $raw = <<<'TXT'
January
World Braille Day (A/RES/73/161)
04 Jan
International Day of Education (A/RES/73/25)
24 Jan
International Day of Clean Energy (A/RES/77/327)
26 Jan
International Day of Commemoration in Memory of the Victims of the Holocaust (A/RES/60/7)
27 Jan
International Day of Peaceful Coexistence (A/RES/79/269)
28 Jan
February
World Interfaith Harmony Week, 1-7 February (A/RES/65/5)
01 Feb
World Wetlands Day (A/RES/75/317)
02 Feb
International Day of Human Fraternity (A/RES/75/200)
04 Feb
International Day of Zero Tolerance to Female Genital Mutilation (A/RES/67/146)
06 Feb
World Pulses Day (A/RES/73/251)
10 Feb
International Day of the Arabian Leopard (A/RES/77/295)
10 Feb
International Day of Women and Girls in Science (A/RES/70/212)
11 Feb
International Day for the Prevention of Violent Extremism as and when Conducive to Terrorism (A/RES/77/243)
12 Feb
World Radio Day (A/RES/67/124)
13 Feb
Global Tourism Resilience Day (A/RES/77/269)
17 Feb
World Day of Social Justice (A/RES/62/10)
20 Feb
International Mother Language Day (A/RES/56/262)
21 Feb
March
Zero Discrimination Day [UNAIDS]
01 Mar
World Seagrass Day (A/RES/76/265)
01 Mar
World Wildlife Day (A/RES/68/205)
03 Mar
International Day for Disarmament and Non-Proliferation Awareness (A/RES/77/51)
05 Mar
International Women's Day
08 Mar
International Day of Women Judges (A/RES/75/274)
10 Mar
International Day to Combat Islamophobia (A/RES/76/254)
15 Mar
International Day of Happiness (A/RES/66/281)
20 Mar
French Language Day
20 Mar
World Day for Glaciers (A/RES/77/158)
21 Mar
Week of Solidarity with the Peoples Struggling against Racism and Racial Discrimination, 21-27 March (A/RES/34/24)
21 Mar
International Day for the Elimination of Racial Discrimination (A/RES/2142 (XXI))
21 Mar
International Day of Forests (A/RES/67/200)
21 Mar
World Poetry Day [UNESCO] (30 C/Resolution 29)
21 Mar
International Day of Nowruz (A/RES/64/253)
21 Mar
World Down Syndrome Day (A/RES/66/149)
21 Mar
World Water Day (A/RES/47/193)
22 Mar
World Meteorological Day [WMO] (WMO/EC-XII/Res.6)
23 Mar
World Tuberculosis Day [WHO]
24 Mar
International Day for the Right to the Truth concerning Gross Human Rights Violations and for the Dignity of Victims (A/RES/65/196)
24 Mar
International Day of Remembrance of the Victims of Slavery and the Transatlantic Slave Trade (A/RES/62/122)
25 Mar
International Day of Solidarity with Detained and Missing Staff Members (A/RES/49/59)
25 Mar
International Day of Zero Waste (A/RES/77/161)
30 Mar
April
World Autism Awareness Day (A/RES/62/139)
02 Apr
International Day for Mine Awareness and Assistance in Mine Action (A/RES/60/97)
04 Apr
International Day of Conscience (A/RES/73/329)
05 Apr
International Day of Sport for Development and Peace (A/RES/67/296)
06 Apr
International Day of Reflection on the 1994 Genocide against the Tutsi in Rwanda (A/RES/58/234)
07 Apr
World Health Day [WHO] (WHA/A.2/Res.35)
07 Apr
International Day of Human Space Flight (A/RES/65/271)
12 Apr
World Chagas Disease Day [WHO]
14 Apr
Chinese Language Day
20 Apr
World Creativity and Innovation Day (A/RES/71/284)
21 Apr
International Mother Earth Day (A/RES/63/278)
22 Apr
World Book and Copyright Day [UNESCO] (UNESCO 28 C/Resolution 3.18)
23 Apr
English Language Day
23 Apr
Spanish Language Day
23 Apr
International Girls in ICT Day [ITU]
24 Apr
World Immunization Week, 24-30 April [WHO]
24 Apr
International Day of Multilateralism and Diplomacy for Peace (A/RES/73/127)
24 Apr
World Malaria Day [WHO]
25 Apr
International Delegate’s Day (A/RES/73/286)
25 Apr
International Chernobyl Disaster Remembrance Day (A/RES/71/125)
26 Apr
World Intellectual Property Day [WIPO]
26 Apr
World Day for Safety and Health at Work
28 Apr
International Day in Memory of the Victims of Earthquakes (A/RES/79/285)
29 Apr
International Jazz Day (UNESCO 36 C/Resolution 39)
30 Apr
May
World Tuna Day (A/RES/71/124)
02 May
World Press Freedom Day (UNESCO 26 C/Resolution 4.3)
03 May
World Portuguese Language Day [UNESCO]
05 May
Time of Remembrance and Reconciliation for Those Who Lost Their Lives During the Second World War (A/RES/59/26)
08 May
International Day of Argania (A/RES/75/262)
10 May
World Migratory Bird Day [UNEP]
10 May
International Day of Plant Health [FAO] (A/RES/76/256)
12 May
UN Global Road Safety Week (biennial) [WHO]
12 May
Vesak, the Day of the Full Moon (A/RES/54/115)
12 May
International Day of Families (A/RES/47/237)
15 May
International Day of Living Together in Peace (A/RES/72/130)
16 May
International Day of Light [UNESCO] (39 C/Resolution 16)
16 May
World Telecommunication and Information Society Day (A/RES/60/252)
17 May
World Fair Play Day (A/RES/78/310)
19 May
World Bee Day (A/RES/72/211)
20 May
International Tea Day (A/RES/74/241)
21 May
World Day for Cultural Diversity for Dialogue and Development (A/RES/57/249)
21 May
International Day for Biological Diversity (A/RES/55/201)
22 May
International Day to End Obstetric Fistula (A/RES/67/147)
23 May
International Day of the Markhor (A/RES/78/278)
24 May
World Football Day (A/RES/78/281)
25 May
Week of Solidarity with the Peoples of Non-Self-Governing Territories, 25-31 May (A/RES/54/91)
25 May
International Day of UN Peacekeepers (A/RES/57/129)
29 May
International Day of Potato (A/RES/78/123)
30 May
World No-Tobacco Day [WHO] (Resolution 42.19)
31 May
June
Global Day of Parents (A/RES/66/292)
01 Jun
World Bicycle Day (A/RES/72/272)
03 Jun
International Day of Innocent Children Victims of Aggression (A/RES/ES-7/8)
04 Jun
World Environment Day (A/RES/2994 (XXVII))
05 Jun
International Day for the Fight against Illegal, Unreported and Unregulated Fishing (A/RES/72/72)
05 Jun
Russian Language Day
06 Jun
World Food Safety Day (A/RES/73/250)
07 Jun
World Oceans Day (A/RES/63/111)
08 Jun
International Day for Dialogue among Civilizations (A/RES/78/286)
10 Jun
International Day of Play (A/RES/78/268)
11 Jun
World Day Against Child Labour
12 Jun
International Albinism Awareness Day (A/RES/69/170)
13 Jun
World Blood Donor Day [WHO] (WHA Resolution 58.13)
14 Jun
World Elder Abuse Awareness Day (A/RES/66/127)
15 Jun
International Day of Family Remittances (A/RES/72/281)
16 Jun
World Day to Combat Desertification and Drought (A/RES/49/115)
17 Jun
Sustainable Gastronomy Day (A/RES/71/246)
18 Jun
International Day for Countering Hate Speech (A/RES/75/309)
18 Jun
International Day for the Elimination of Sexual Violence in Conflict (A/RES/69/293)
19 Jun
World Refugee Day (A/RES/55/76)
20 Jun
International Day of Yoga (A/RES/69/131)
21 Jun
International Day of the Celebration of the Solstice (A/RES/73/300)
21 Jun
United Nations Public Service Day (A/RES/57/277)
23 Jun
International Widows' Day (A/RES/65/189)
23 Jun
International Day of Women in Diplomacy (A/RES/76/269)
24 Jun
Day of the Seafarer [IMO] (STCW/CONF.2/DC/4)
25 Jun
International Day against Drug Abuse and Illicit Trafficking (A/RES/42/112)
26 Jun
United Nations International Day in Support of Victims of Torture (A/RES/52/149)
26 Jun
International Day of Deafblindness (A/RES/79/294)
27 Jun
Micro-, Small and Medium-sized Enterprises Day (A/RES/71/279)
27 Jun
International Day of the Tropics (A/RES/71/279)
29 Jun
International Asteroid Day (A/RES/71/90)
30 Jun
International Day of Parliamentarism (A/RES/72/278)
30 Jun
July
International Day of Cooperatives (A/RES/47/90)
05 Jul
World Rural Development Day (A/RES/78/326)
06 Jul
World Kiswahili Language Day (A/RES/78/312)
07 Jul
World Horse Day (A/RES/79/291)
11 Jul
World Population Day (A/RES/45/216)
11 Jul
International Day of Reflection and Commemoration of the 1995 Genocide in Srebrenica (A/RES/78/282)
11 Jul
International Day of Combating Sand and Dust Storms (A/RES/77/294)
12 Jul
International Day of Hope (A/RES/79/270)
12 Jul
World Youth Skills Day (A/RES/69/145)
15 Jul
Nelson Mandela International Day (A/RES/64/13)
18 Jul
World Chess Day (A/RES/74/22)
20 Jul
International Moon Day (A/RES/76/76)
20 Jul
International Day of Women and Girls of African Descent (A/RES/78/323)
25 Jul
World Drowning Prevention Day (A/RES/75/273)
25 Jul
International Day for Judicial Well-being (A/RES/79/266)
25 Jul
World Hepatitis Day [WHO]
28 Jul
International Day of Friendship (A/RES/65/275)
30 Jul
World Day against Trafficking in Persons (A/RES/68/192)
30 Jul
August
World Breastfeeding Week, 1-7 August [WHO]
01 Aug
International Day of Awareness of the Special Development Needs and Challenges of Landlocked Developing Countries (A /79/L.108)
06 Aug
International Day of the World's Indigenous Peoples (A/RES/49/214)
09 Aug
World Steelpan Day (A/RES/77/316)
11 Aug
International Youth Day (A/RES/54/120)
12 Aug
World Humanitarian Day (A/RES/63/139)
19 Aug
International Day of Remembrance and Tribute to the Victims of Terrorism (A/RES/72/165)
21 Aug
International Day Commemorating the Victims of Acts of Violence Based on Religion or Belief (A/RES/73/296)
22 Aug
International Day for the Remembrance of the Slave Trade and Its Abolition [UNESCO] (29 C/Resolution 40)
23 Aug
World Lake Day (A/RES/79/142)
27 Aug
International Day against Nuclear Tests (A/RES/64/35)
29 Aug
International Day of the Victims of Enforced Disappearances (A/RES/65/209)
30 Aug
International Day for People of African Descent (A/RES/75/170)
31 Aug
September
International Day of Charity (A/RES/67/105)
05 Sep
World Duchenne Awareness Day (A/RES/78/12)
07 Sep
International Day of Clean Air for Blue Skies (A/RES/74/212)
07 Sep
International Day of Police Cooperation (A/RES/77/241)
07 Sep
International Literacy Day [UNESCO] (UNESCO 14 C/Resolution 1.441)
08 Sep
International Day to Protect Education from Attack (A/RES/74/275)
09 Sep
United Nations Day for South-South Cooperation (A/RES/58/220)
12 Sep
International Day of Democracy (A/RES/62/7)
15 Sep
International Day of Science, Technology and Innovation for the South (A/RES/78/259)
16 Sep
International Day for Interventional Cardiology (A/RES/76/302)
16 Sep
International Day for the Preservation of the Ozone Layer (A/RES/49/114)
16 Sep
World Patient Safety Day [WHO]
17 Sep
International Equal Pay Day (A/RES/74/142)
18 Sep
World Cleanup Day (A/RES/78/122)
20 Sep
International Day of Peace (A/RES/36/67)
21 Sep
International Day of Sign Languages (A/RES/72/161)
23 Sep
World Maritime Day
25 Sep
International Day for the Total Elimination of Nuclear Weapons (A/RES/68/32)
26 Sep
World Tourism Day
27 Sep
International Day for Universal Access to Information (A/RES/74/5)
28 Sep
International Day of Awareness of Food Loss and Waste (A/RES/74/209)
29 Sep
International Translation Day (A/RES/71/288)
30 Sep
October
International Day of Older Persons (A/RES/45/106)
01 Oct
International Day of Non-Violence (A/RES/61/271)
02 Oct
World Space Week, 4-10 October (A/RES/54/68)
04 Oct
World Teachers’ Day [UNESCO] ((27 C/INF.7))
05 Oct
World Habitat Day (A/RES/40/202 A)
06 Oct
World Cotton Day (A/RES/75/318)
07 Oct
World Post Day
09 Oct
World Mental Health Day [WHO]
10 Oct
International Day of the Girl Child (A/RES/66/170)
11 Oct
World Migratory Bird Day [UNEP]
12 Oct
International Day for Disaster Risk Reduction (A/RES/64/200)
13 Oct
International Day of Rural Women (A/RES/62/136)
15 Oct
World Food Day [FAO] (A/RES/35/70)
16 Oct
International Day for the Eradication of Poverty (A/RES/47/196)
17 Oct
World Statistics Day (A/RES/69/282)
20 Oct
International Day of the Snow Leopard (A/RES/79/143)
23 Oct
United Nations Day (A/RES/168 (II))
24 Oct
Disarmament Week, 24-30 October (A/RES/S-10/2 (p. 102))
24 Oct
World Development Information Day (A/RES/3038 (XXVII))
24 Oct
Global Media and Information Literacy Week, 24-31 October (A/RES/75/267)
24 Oct
World Day for Audiovisual Heritage [UNESCO] (UNESCO 33/C/Resolution 5)
27 Oct
International Day of Care and Support (A/RES/77/317)
29 Oct
World Cities Day (A/RES/68/239)
31 Oct
November
International Day to End Impunity for Crimes against Journalists (A/RES/68/163)
02 Nov
World Tsunami Awareness Day (A/RES/70/203)
05 Nov
International Day for Preventing the Exploitation of the Environment in War and Armed Conflict (A/RES/56/4)
06 Nov
International Week of Science and Peace, 9-15 November (A/RES/43/61)
09 Nov
World Science Day for Peace and Development (UNESCO 31 C/Resolution 20)
10 Nov
World Diabetes Day (A/RES/61/225)
14 Nov
International Day for the Prevention of and Fight against All Forms of Transnational Organized Crime (A/RES/78/267)
15 Nov
International Day for Tolerance [UNESCO] (28 C/Resolution 5.61)
16 Nov
World Day of Remembrance for Road Traffic Victims (A/RES/60/5)
17 Nov
World Antimicrobial Resistance Awareness Week, 18-24 November [WHO]
18 Nov
World Day for the Prevention of and Healing from Child Sexual Exploitation, Abuse and Violence (A/RES/77/8)
18 Nov
World Toilet Day (A/RES/67/291)
19 Nov
World Philosophy Day [UNESCO] (33 C/Resolution 37)
21 Nov
Africa Industrialization Day (A/RES/44/237)
20 Nov
World Children's Day (A/RES/836(IX))
20 Nov
World Television Day (A/RES/51/205)
21 Nov
World Conjoined Twins Day (A/RES/78/313)
24 Nov
International Day for the Elimination of Violence against Women (A/RES/54/134)
25 Nov
World Sustainable Transport Day (A/RES/77/286)
26 Nov
International Day of Solidarity with the Palestinian People (A/RES/32/40B)
29 Nov
Day of Remembrance for all Victims of Chemical Warfare (OPCW C-20/DEC.10)
30 Nov
December
World AIDS Day
01 Dec
International Day for the Abolition of Slavery (A/RES/317(IV))
02 Dec
International Day of Persons with Disabilities (A/RES/47/3)
03 Dec
International Day of Banks (A/RES/74/245)
04 Dec
International Day Against Unilateral Coercive Measures (A/RES/79/293)
04 Dec
International Volunteer Day for Economic and Social Development (A/RES/40/212)
05 Dec
World Soil Day (A/RES/68/232)
05 Dec
International Civil Aviation Day (A/RES/51/33)
07 Dec
International Day of Commemoration and Dignity of the Victims of the Crime of Genocide and of the Prevention of this Crime (A/RES/69/323)
09 Dec
International Anti-Corruption Day (A/RES/58/4)
09 Dec
Human Rights Day (A/RES/423 (V))
10 Dec
International Mountain Day (A/RES/57/245)
11 Dec
International Day of Neutrality (A/RES/71/275)
12 Dec
International Universal Health Coverage Day (A/RES/72/138)
12 Dec
International Migrants Day (A/RES/55/93)
18 Dec
Arabic Language Day
18 Dec
International Human Solidarity Day (A/RES/60/209)
20 Dec
World Meditation Day (A/RES/79/137)
21 Dec
World Basketball Day (A/RES/77/324)
21 Dec
International Day of Epidemic Preparedness (A/RES/75/27)
27 Dec
TXT;

        $monthMap = [
            'jan' => 1,'feb' => 2,'mar' => 3,'apr' => 4,'may' => 5,'jun' => 6,
            'jul' => 7,'aug' => 8,'sep' => 9,'oct' => 10,'nov' => 11,'dec' => 12,
        ];

        $lines = preg_split('/\r?\n/', $raw);
        $dateRe = '/^(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/i';
        $cleanTitle = function(string $t): string {
            // remove bracketed tags anywhere: [WHO], [UNESCO], etc.
            $t = preg_replace('/\s*\[[^\]]*\]\s*/u', ' ', $t);
            // repeatedly remove trailing parenthetical groups like (A/RES/xx), ((...)) at the end
            do {
                $prev = $t;
                $t = preg_replace('/\s*\([^()]*\)\s*$/u', '', $t);
            } while ($t !== $prev);
            // collapse spaces
            $t = preg_replace('/\s+/u', ' ', $t);
            return trim($t);
        };

        // Collect titles keyed by MM-DD
        $byDate = [];
        $pendingTitle = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' ) continue;

            // If line is a date, attach the previously seen title
            if (preg_match($dateRe, $line, $m)) {
                $day = (int)$m[1];
                $mon = strtolower(substr($m[2], 0, 3));
                $mm = $monthMap[$mon] ?? 0;
                if ($mm && $pendingTitle) {
                    $key = sprintf('%02d-%02d', $mm, $day);
                    $byDate[$key] = $byDate[$key] ?? [];
                    $byDate[$key][] = $pendingTitle;
                    $pendingTitle = null;
                }
                continue;
            }

            // Skip month header lines
            if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)$/i', $line)) {
                $pendingTitle = null;
                continue;
            }

            // Otherwise this is a title line; hold until we see its date line next
            $pendingTitle = $cleanTitle($line);
        }

        // Build rows: join multiple titles with numbered lines (1- , 2- , ...)
        $rows = [];
        foreach ($byDate as $md => $titles) {
            $numbered = [];
            foreach ($titles as $i => $t) {
                $numbered[] = ($i + 1) . '- ' . $t;
            }
            $rows[] = [
                'date' => '2000-' . $md,
                'name' => implode("\n", $numbered),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Sort by date for determinism
        usort($rows, function($a, $b){ return strcmp($a['date'], $b['date']); });

        DB::table('public_events')->insert($rows);
    }
}
