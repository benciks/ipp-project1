Implementační dokumentace k 1. úloze do IPP 2022/2023
Jméno a příjmení: Šimon Benčík
Login: xbenci01

# Implementácia
### Príprava
Vstup z stdin ako prvé vstup podľa znaku nového riadku na jednotlivé riadky, ktoré prezentujú prvky v poli. Následne odstraňujem komentáre a prázdne riadky pre jednoduchšiu syntaktickú a lexikálnu analýzu. 

V tomto kroku taktiež pozorujem prítomnosť a správny zápis záhlavia IPPCode. Pre tvorbu XML kódu som sa rozhodol použiť knižnicu SimpleXML a preto ešte pred analýzou vytváram novú instanciu triedy tejto knižnice, ktorá predstavuje koreňový element program.

### Parsing
Samotný parsing je riešený pomocou cyklu, ktorý prechádza jednotlivé riadky vstupu a prepínača pre jednolivé inštrukcie. Na začiatku každého vstupu rozdeľujem riadok podľa medzier, prebytočné medzery prípadne odstraňujem. Kedže rôzne inštrukcie príjmajú rovnaký počet a typ argumentov, snažil som sa zoskupiť čo najviac inštrukcií aby som sa vyhol redundantnému kódu. 

Pri zhode inštrukcie sledujem správny počet argumentov a následne voľám funkcie na skontrolovanie jednotlivých typov argumentu. Pre každý typ som si predpripravil funkcie, ktoré pomocou regulárnych výrazov kontroľujú premenné, náveštia a symboly rôznych typov. Pri type int kontrolujem či ide o hexadecimálne, oktálne alebo decimálne čislo, používam na to dva regulárne výrazy. 

Ak sa nájde syntaktická chyba, volaná funkcia priamo ukončuje program s chybou 23. Ak prebehnú v poriadku, tieto funkcie vracajú hodnotu a typ argumentu, ktoré su predané funkcii addInstruction, ktorá generuje instruction elementy a elementy pre argumenty.

Po skončení cyklu a teda skončení parsovania jednotlivých riadkov volám novú instanciu triedy knižnice DomElement, ktorú používam na formátovanie výstupného XML na stdout.


