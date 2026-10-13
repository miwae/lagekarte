<?php
/**
 * Plugin Name: Planungs-Tool Seiten Bootstrap
 * Description: Legt die Tool-Seiten für planungs-tool.de automatisch an und synchronisiert ihre Shortcodes.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ptpb_render_tool_page( array $page ): string {
    $highlights = '';

    if ( ! empty( $page['highlights'] ) && is_array( $page['highlights'] ) ) {
        $highlights = '<h3>Schnellueberblick</h3><ul>';

        foreach ( $page['highlights'] as $item ) {
            $highlights .= '<li>' . esc_html( $item ) . '</li>';
        }

        $highlights .= '</ul>';
    }

    $hint = '';

    if ( ! empty( $page['hint'] ) ) {
        $hint = '<p><strong>Hinweis:</strong> ' . esc_html( $page['hint'] ) . '</p>';
    }

    return '<p>' . esc_html( $page['intro'] ) . '</p>'
        . $highlights
        . $hint
        . '<hr>'
        . $page['shortcode']
        . '<hr>'
        . '<p><a href="/einsatz-tools/">Zurueck zur Uebersicht Einsatz-Tools</a></p>';
}

function ptpb_overview_page_definition(): array {
    return [
        'slug'    => 'einsatz-tools',
        'title'   => 'Einsatz-Tools',
        'content' => '<h2>Einsatz-Tools</h2>'
            . '<p>Diese Uebersicht buendelt die einsatzrelevanten Werkzeuge fuer Fuehrung, Lagefeststellung, Dokumentation und Nachbereitung.</p>'
            . '<p>Die Seiten sind fuer den direkten Einsatzbetrieb gedacht: schnell oeffnen, Daten erfassen, im Browser weiterarbeiten und bei Bedarf drucken.</p>'
            . '<h3>Verfuegbare Werkzeuge</h3>'
            . '<ul>'
            . '<li><a href="/einsatz-tools/gefahrstoff-ki/">Gefahrstoff KI</a></li>'
            . '<li><a href="/einsatz-tools/manv-triage/">MANV-Triage</a></li>'
            . '<li><a href="/einsatz-tools/wetter/">Wetter am Einsatzort</a></li>'
            . '<li><a href="/einsatz-tools/kraefte/">Kraeftestaerke-Rechner</a></li>'
            . '<li><a href="/einsatz-tools/checklisten/">Einsatz-Checklisten</a></li>'
            . '<li><a href="/einsatz-tools/meldevorlagen/">Meldevorlagen</a></li>'
            . '<li><a href="/einsatz-tools/schichtplanung/">Schichtplanung</a></li>'
            . '<li><a href="/einsatz-tools/lagebeurteilung-ki/">KI-Lagebeurteilung</a></li>'
            . '<li><a href="/einsatz-tools/nachbesprechung/">KI-Nachbesprechung</a></li>'
            . '</ul>'
            . '<h3>Einsatzempfehlung</h3>'
            . '<p>Fuer die Lagefuehrung bietet sich die Reihenfolge an: Wetter und Lage erkunden, Kraefte erfassen, Meldevorlagen nutzen, dann Lagebeurteilung und spaeter Nachbesprechung dokumentieren.</p>',
    ];
}

function ptpb_page_definitions(): array {
    $pages = [
        [
            'slug'    => 'gefahrstoff-ki',
            'title'   => 'Gefahrstoff KI',
            'intro'   => 'Der Gefahrstoffassistent liefert eine strukturierte Ersteinschaetzung zu Stoffen, GHS-Hinweisen, Erstmassnahmen, PSA und Loeschmitteln.',
            'highlights' => [
                'Schnelle Stoffeinschaetzung fuer die erste Lagefeststellung',
                'Unterstuetzung fuer GAMS-Regel, PSA und Einsatzschutz',
                'Geeignet fuer Rueckfragen im Fuehrungsfahrzeug oder an der Einsatzstelle',
            ],
            'hint'      => 'Die KI-Ausgabe ersetzt nicht Sicherheitsdatenblatt, Fachberater oder Herstellerinformationen.',
            'shortcode' => '[gefahrstoff_ki]',
        ],
        [
            'slug'    => 'manv-triage',
            'title'   => 'MANV-Triage',
            'intro'   => 'Diese Seite dient zur schnellen Uebersicht der Sichtungskategorien und unterstuetzt die Fuehrung am Behandlungsplatz oder Bereitstellungsraum.',
            'highlights' => [
                'SK I bis IV sowie Tote sauber zaehlen',
                'Abtransportierte Patienten getrennt erfassen',
                'Notizen fuer BHP-Leitung und Transportorganisation festhalten',
            ],
            'hint'      => 'Fuer Lagedarstellung regelmaessig mit den Transport- und Sichtungszahlen abgleichen.',
            'shortcode' => '[manv_triage]',
        ],
        [
            'slug'    => 'wetter',
            'title'   => 'Wetter am Einsatzort',
            'intro'   => 'Wetterdaten am Einsatzort helfen bei Anfahrt, Taktik, Einsatzschutz und besonders bei ABC-Lagen oder Rauchausbreitung.',
            'highlights' => [
                'Aktuelle Windrichtung und Windgeschwindigkeit',
                '12-Stunden-Vorschau fuer weitere Einsatzplanung',
                'Direkter Hinweis bei windkritischen Lagen',
            ],
            'hint'      => 'Bei Gefahrstofflagen und Brandeinsaetzen immer mit eigener Erkundung und Oertlichkeit gegenpruefen.',
            'shortcode' => '[wetter_einsatzort]',
        ],
        [
            'slug'    => 'kraefte',
            'title'   => 'Kraeftestaerke-Rechner',
            'intro'   => 'Hier koennen eingesetzte Einheiten, Fuehrungskraefte, Unterfuehrer, Mannschaften und Fahrzeuge uebersichtlich zusammengefuehrt werden.',
            'highlights' => [
                'BOS-Staerkemeldung im Format F/U/M gleich Gesamt',
                'Mehrere Organisationen in einer Ansicht',
                'Druckbar fuer Fuehrungsunterlagen und Lagebesprechung',
            ],
            'hint'      => 'Die Eingaben eignen sich gut fuer Lagekarte, Fuehrungsnachweis und Meldungen an die uebergeordnete Fuehrung.',
            'shortcode' => '[kraefte_rechner]',
        ],
        [
            'slug'    => 'checklisten',
            'title'   => 'Einsatz-Checklisten',
            'intro'   => 'Diese Checklisten unterstuetzen Fuehrung und Einsatzabwicklung in typischen BOS-Lagen von Erkundung bis Nachsorge.',
            'highlights' => [
                'Vordefinierte Listen fuer GAMS, Erkundung und BHP',
                'Fortschrittsanzeige direkt im Browser',
                'Geeignet als Gedankenstuetze unter Zeitdruck',
            ],
            'hint'      => 'Checklisten helfen gegen Auslassungen, ersetzen aber keine eigene Fuehrungsentscheidung.',
            'shortcode' => '[einsatz_checklisten]',
        ],
        [
            'slug'    => 'meldevorlagen',
            'title'   => 'Meldevorlagen',
            'intro'   => 'Standardisierte Meldungen sparen Zeit und verbessern die Qualitaet von Rueckmeldungen an Leitstelle, Einsatzleitung und Stab.',
            'highlights' => [
                'Lagemeldung nach Fuehrungsgrundsaetzen',
                'SITRAP fuer strukturierte Lagefortschreibung',
                'Anforderung von Kraeften und Mitteln mit sauberer Vorschau',
            ],
            'hint'      => 'Vor dem Senden oder Funken die Vorschau kurz auf Vollstaendigkeit und Verstaendlichkeit pruefen.',
            'shortcode' => '[meldevorlagen]',
        ],
        [
            'slug'    => 'schichtplanung',
            'title'   => 'Schichtplanung',
            'intro'   => 'Die Schichtplanung hilft bei laenger andauernden Einsatzlagen, damit Ablosungen, Funktionen und Durchhaltefaehigkeit frueh organisiert werden.',
            'highlights' => [
                'Drei Schichten mit Zeitfenstern und Zuordnung',
                'Geeignet fuer Fuehrungsstaffel, Abschnitt oder Stabsbetrieb',
                'Schneller Ueberblick ueber Personal und Funktion',
            ],
            'hint'      => 'Spaetestens bei laengerer Einsatzdauer Reserven, Ruhezeiten und Fuehrungsabloesung mitdenken.',
            'shortcode' => '[schichtplanung]',
        ],
        [
            'slug'    => 'lagebeurteilung-ki',
            'title'   => 'KI-Lagebeurteilung',
            'intro'   => 'Die KI-Lagebeurteilung unterstuetzt die strukturierte Analyse einer Einsatzlage nach Fuehrungsvorgang und kann als Denk- und Formulierungshilfe dienen.',
            'highlights' => [
                'Lagefeststellung, Beurteilung und Fuehrungsentschluss in Struktur',
                'Sofortmassnahmen und empfohlene Kraefte als Arbeitshilfe',
                'Hilfreich fuer Abschnittsleitung, Einsatzleitung und Stabsarbeit',
            ],
            'hint'      => 'Die KI bewertet nur die eingegebenen Informationen. Kritische Entscheidungen muessen fachlich verifiziert werden.',
            'shortcode' => '[lagebeurteilung_ki]',
        ],
        [
            'slug'    => 'nachbesprechung',
            'title'   => 'KI-Nachbesprechung',
            'intro'   => 'Die Nachbesprechung fasst den Einsatz strukturiert zusammen und unterstuetzt Lessons Learned, Dokumentation und organisatorische Nachbereitung.',
            'highlights' => [
                'Zusammenfassung und Zeitlinie des Einsatzes',
                'Positive Punkte und Verbesserungsfelder getrennt dargestellt',
                'Konkrete Empfehlungen fuer kuenftige Einsaetze',
            ],
            'hint'      => 'Fuer belastbare Lessons Learned sollten Rueckmeldungen mehrerer Beteiligter einfliessen.',
            'shortcode' => '[nachbesprechung_ki]',
        ],
    ];

    foreach ( $pages as &$page ) {
        $page['content'] = ptpb_render_tool_page( $page );
    }

    unset( $page );

    return $pages;
}

function ptpb_upsert_page( array $page, int $parent_id = 0 ): int {
    $existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
    $payload  = [
        'post_title'   => $page['title'],
        'post_name'    => $page['slug'],
        'post_content' => $page['content'],
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => $parent_id,
    ];

    if ( $existing instanceof WP_Post ) {
        $needs_update = $existing->post_title !== $page['title']
            || trim( (string) $existing->post_content ) !== $page['content']
            || $existing->post_status !== 'publish'
            || (int) $existing->post_parent !== $parent_id;

        if ( $needs_update ) {
            $payload['ID'] = $existing->ID;
            wp_update_post( wp_slash( $payload ) );
        }

        return (int) $existing->ID;
    }

    return (int) wp_insert_post( wp_slash( $payload ) );
}

function ptpb_sync_tool_pages(): void {
    if ( ! function_exists( 'get_page_by_path' ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $overview = ptpb_overview_page_definition();
    $pages    = ptpb_page_definitions();
    $hash     = md5( wp_json_encode( [
        'overview' => $overview,
        'pages'    => $pages,
    ] ) );

    if ( get_option( 'ptpb_pages_hash' ) === $hash ) {
        return;
    }

    $overview_id = ptpb_upsert_page( $overview, 0 );

    foreach ( $pages as $page ) {
        ptpb_upsert_page( $page, $overview_id );
    }

    update_option( 'ptpb_pages_hash', $hash, false );
    flush_rewrite_rules( false );
}

add_action( 'admin_init', 'ptpb_sync_tool_pages' );