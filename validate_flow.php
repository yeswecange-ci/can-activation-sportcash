<?php

/**
 * Validation du Flow Twilio
 * Vérifie que toutes les transitions pointent vers des widgets existants
 */

$json = file_get_contents('twilio_flow_optimized.json');
$flow = json_decode($json, true);

if (!$flow) {
    die("❌ Erreur JSON : " . json_last_error_msg() . "\n");
}

echo "🔍 Validation du Flow Twilio\n";
echo str_repeat("=", 60) . "\n\n";

// Récupérer tous les noms de widgets
$widgetNames = [];
foreach ($flow['states'] as $state) {
    $widgetNames[] = $state['name'];
}

echo "📊 Total widgets : " . count($widgetNames) . "\n\n";
echo "✅ Widgets trouvés :\n";
foreach ($widgetNames as $name) {
    echo "  - $name\n";
}
echo "\n";

// Vérifier toutes les transitions
$errors = [];
$warnings = [];

foreach ($flow['states'] as $state) {
    $widgetName = $state['name'];

    if (isset($state['transitions'])) {
        foreach ($state['transitions'] as $transition) {
            if (isset($transition['next'])) {
                $targetWidget = $transition['next'];

                if (!in_array($targetWidget, $widgetNames)) {
                    $errors[] = "❌ Widget '$widgetName' → transition vers '$targetWidget' (INEXISTANT)";
                }
            }
        }
    }
}

// Vérifier les références dans les conditions
foreach ($flow['states'] as $state) {
    $widgetName = $state['name'];
    $type = $state['type'];

    // Vérifier les références de widgets dans les inputs
    if ($type === 'split-based-on' && isset($state['properties']['input'])) {
        $input = $state['properties']['input'];

        // Extraire les noms de widgets référencés (ex: {{widgets.http_get_matchs_new.parsed...}})
        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $input, $matches)) {
            foreach ($matches[1] as $referencedWidget) {
                if (!in_array($referencedWidget, $widgetNames)) {
                    $warnings[] = "⚠️  Widget '$widgetName' référence '$referencedWidget' dans input (non trouvé)";
                }
            }
        }
    }

    // Vérifier les send-and-wait-for-reply body
    if ($type === 'send-and-wait-for-reply' && isset($state['properties']['body'])) {
        $body = $state['properties']['body'];

        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $body, $matches)) {
            foreach ($matches[1] as $referencedWidget) {
                if (!in_array($referencedWidget, $widgetNames)) {
                    $warnings[] = "⚠️  Widget '$widgetName' référence '$referencedWidget' dans body (non trouvé)";
                }
            }
        }
    }

    // Vérifier les conditions dans check_choix_prono
    if ($type === 'split-based-on' && isset($state['transitions'])) {
        foreach ($state['transitions'] as $transition) {
            if (isset($transition['conditions'])) {
                foreach ($transition['conditions'] as $condition) {
                    if (isset($condition['arguments'][0])) {
                        $arg = $condition['arguments'][0];

                        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $arg, $matches)) {
                            foreach ($matches[1] as $referencedWidget) {
                                if (!in_array($referencedWidget, $widgetNames)) {
                                    $warnings[] = "⚠️  Widget '$widgetName' référence '$referencedWidget' dans condition (non trouvé)";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Afficher les résultats
echo str_repeat("=", 60) . "\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ AUCUNE ERREUR TROUVÉE !\n";
    echo "Le flow est valide et peut être importé dans Twilio Studio.\n";
} else {
    if (!empty($errors)) {
        echo "❌ ERREURS CRITIQUES (" . count($errors) . ") :\n";
        foreach ($errors as $error) {
            echo "$error\n";
        }
        echo "\n";
    }

    if (!empty($warnings)) {
        echo "⚠️  AVERTISSEMENTS (" . count($warnings) . ") :\n";
        foreach ($warnings as $warning) {
            echo "$warning\n";
        }
        echo "\n";
    }
}

echo str_repeat("=", 60) . "\n";

// Vérifier les widgets utilisés vs définis
echo "\n🔎 Vérification des références de widgets...\n\n";

$referencedWidgets = [];
foreach ($flow['states'] as $state) {
    if (isset($state['properties']['input'])) {
        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $state['properties']['input'], $matches)) {
            $referencedWidgets = array_merge($referencedWidgets, $matches[1]);
        }
    }

    if (isset($state['properties']['body'])) {
        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $state['properties']['body'], $matches)) {
            $referencedWidgets = array_merge($referencedWidgets, $matches[1]);
        }
    }

    if (isset($state['properties']['variables'])) {
        foreach ($state['properties']['variables'] as $var) {
            if (isset($var['value']) && preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $var['value'], $matches)) {
                $referencedWidgets = array_merge($referencedWidgets, $matches[1]);
            }
        }
    }

    if (isset($state['transitions'])) {
        foreach ($state['transitions'] as $transition) {
            if (isset($transition['conditions'])) {
                foreach ($transition['conditions'] as $condition) {
                    if (isset($condition['arguments'][0])) {
                        if (preg_match_all('/\{\{widgets\.([a-zA-Z0-9_]+)\./', $condition['arguments'][0], $matches)) {
                            $referencedWidgets = array_merge($referencedWidgets, $matches[1]);
                        }
                    }
                }
            }
        }
    }
}

$referencedWidgets = array_unique($referencedWidgets);
sort($referencedWidgets);

echo "Widgets référencés dans le flow :\n";
foreach ($referencedWidgets as $widget) {
    $exists = in_array($widget, $widgetNames);
    echo ($exists ? "  ✅" : "  ❌") . " $widget" . ($exists ? "" : " (MANQUANT)") . "\n";
}

echo "\n";

if (count($errors) > 0) {
    echo "⚠️  Des erreurs ont été trouvées. Le flow ne peut pas être importé.\n";
    exit(1);
} else {
    echo "✅ Le flow est valide et prêt pour l'import !\n";
    exit(0);
}
