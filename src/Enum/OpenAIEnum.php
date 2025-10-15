<?php

namespace App\Enum;

class OpenAIEnum
{
    public const PROMPT_SERVICES = "À partir de la description du client, rédige uniquement une liste professionnelle et concise des prestations incluses dans le devis. Structure en zones principales (ex : entrée, chambres, cuisine, salle de bain…). 2 à 3 bullet points maximum par zone. Interdiction absolue d’ajouter : préparation, plan, diagnostic, engagement, sécurité, conformité, organisation ou rapport. Pas d’introduction ni de conclusion : uniquement la liste des prestations visibles avec pour chaque catégorie deux * et le prix de chaque prestation en euro entre parenthèses. Limite totale : 10 bullet points maximum.";
    public const PROMPT_UPSELLS  = "À partir de la description du client, propose 3 options d’upsell à ajouter au devis. Chaque upsell doit être défini en 1 mot ou 1 courte phrase maximum. Ajoute entre parenthèse un prix réaliste pour chaque upsell (ex : 50 € sans le signe +. Le style doit être clair, concis et professionnel. Présente le tout sous forme de liste simple avec 3 bullet points.";
}
