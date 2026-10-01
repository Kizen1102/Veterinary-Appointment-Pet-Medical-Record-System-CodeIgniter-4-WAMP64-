<?php

namespace App\Libraries;

/**
 * Offline answers for the AI Medical Chatbot, used when the AI service is not configured
 * or unavailable: finds veterinary terms in the owner's question and explains them simply.
 */
class VetGlossary
{
    /** Abbreviations are shown in capitals ("BID", not "Bid"). */
    private const ABBREVIATIONS = ['bid', 'sid', 'tid', 'prn', 'po', 'cbc', 'uti', 'dhpp', 'fvrcp', 'nsaid'];

    private const NOTE = "\n\n(Offline glossary answer. This is general information, not a diagnosis.)";

    /** Term => plain-language meaning. Keys are lowercase; the longest match wins. */
    private const TERMS = [
        'atopic dermatitis'   => 'a long-term skin allergy to things in the environment (like dust mites or pollen). It makes the skin itchy and red. It can be controlled but usually not cured.',
        'atopy'               => 'a skin allergy to things in the environment, like dust mites or pollen. It causes itching and red skin.',
        'dermatitis'          => 'inflammation of the skin: it becomes red, itchy or irritated.',
        'pyoderma'            => 'a bacterial infection of the skin, often with small pimples or crusts.',
        'otitis externa'      => 'an infection or inflammation of the outer ear canal. Signs are head shaking, scratching the ear, smell or discharge.',
        'otitis'              => 'inflammation of the ear, usually from an infection.',
        'gastroenteritis'     => 'irritation of the stomach and intestines that causes vomiting and/or diarrhea.',
        'pancreatitis'        => 'inflammation of the pancreas (an organ that helps digest food). It can cause vomiting, belly pain and not eating.',
        'conjunctivitis'      => 'inflammation of the pink lining around the eye ("pink eye"): red, watery or goopy eyes.',
        'cystitis'            => 'inflammation of the bladder. The pet may pee often, strain, or have blood in the urine.',
        'urinary tract infection' => 'a bacterial infection in the bladder or urinary system. The pet may pee often or strain.',
        'uti'                 => 'urinary tract infection: a bacterial infection in the bladder or urinary system.',
        'periodontal disease' => 'gum disease caused by plaque and tartar. It can lead to bad breath, sore gums and loose teeth.',
        'gingivitis'          => 'red, swollen gums, the early stage of gum disease.',
        'dental prophylaxis'  => 'a professional teeth cleaning done under anesthesia.',
        'anesthesia'          => 'medicine that puts the pet into a deep, controlled sleep so it feels no pain during a procedure.',
        'spay'                => 'surgery that removes a female pet\'s uterus and ovaries so she cannot get pregnant.',
        'neuter'              => 'surgery that removes a male pet\'s testicles so he cannot father puppies or kittens.',
        'castration'          => 'surgery that removes a male pet\'s testicles (neutering).',
        'parvovirus'          => 'a very contagious and serious virus in dogs that causes severe vomiting and bloody diarrhea. Vaccines protect against it.',
        'distemper'           => 'a serious virus that affects the lungs, gut and brain. Vaccines protect against it.',
        'rabies'              => 'a deadly virus spread by bites that can also infect people. Vaccination is required by law.',
        'dhpp'                => 'a combination vaccine for dogs against Distemper, Hepatitis, Parvovirus and Parainfluenza.',
        'fvrcp'               => 'a combination vaccine for cats against feline rhinotracheitis, calicivirus and panleukopenia.',
        'booster'             => 'a repeat dose of a vaccine that keeps the protection strong.',
        'heartworm'           => 'a worm spread by mosquito bites that lives in the heart and lungs. Monthly prevention protects your pet.',
        'deworming'           => 'giving medicine that removes intestinal worms.',
        'ectoparasite'        => 'a parasite that lives on the skin, like fleas, ticks or mites.',
        'mange'               => 'a skin disease caused by tiny mites. It causes hair loss and itching.',
        'antibiotic'          => 'medicine that kills bacteria. Give every dose until the course is finished, even if your pet looks better.',
        'anti-inflammatory'   => 'medicine that reduces swelling, redness and pain.',
        'nsaid'               => 'a type of pain and anti-inflammatory medicine. Never give human painkillers to pets.',
        'corticosteroid'      => 'a strong anti-inflammatory medicine (like prednisolone) that calms itching or swelling.',
        'steroid'             => 'a strong anti-inflammatory medicine that calms itching or swelling.',
        'cbc'                 => 'complete blood count: a blood test that checks red cells, white cells and platelets.',
        'blood chemistry'     => 'a blood test that checks how organs like the liver and kidneys are working.',
        'urinalysis'          => 'a urine test that checks the kidneys, bladder and sugar levels.',
        'radiograph'          => 'an X-ray picture of the inside of the body.',
        'ultrasound'          => 'a painless scan that uses sound waves to look at organs.',
        'biopsy'              => 'taking a small piece of tissue to look at under a microscope.',
        'benign'              => 'not cancer, does not spread to other parts of the body.',
        'malignant'           => 'cancer that can grow and spread.',
        'mass'                => 'a lump. The vet may test it to find out what it is.',
        'lethargy'            => 'being unusually tired, weak or uninterested in things.',
        'lethargic'           => 'unusually tired, weak or uninterested in things.',
        'anorexia'            => 'not eating, or eating much less than usual.',
        'inappetence'         => 'eating less than usual or not wanting food.',
        'emesis'              => 'vomiting.',
        'pruritus'            => 'itching.',
        'alopecia'            => 'hair loss.',
        'edema'               => 'swelling caused by fluid under the skin or in tissues.',
        'dehydration'         => 'not enough water in the body. Signs are dry gums, sunken eyes and tiredness.',
        'fever'               => 'a body temperature that is higher than normal, often because of infection.',
        'obesity'             => 'being very overweight, which strains the joints, heart and other organs.',
        'arthritis'           => 'painful, stiff joints that often come with age.',
        'hip dysplasia'       => 'a hip joint that did not form properly, causing pain or limping.',
        'luxating patella'    => 'a kneecap that slips out of place, causing skipping or limping.',
        'diabetes'            => 'a condition where the body cannot control blood sugar. It often needs daily insulin.',
        'hypothyroidism'      => 'an underactive thyroid gland, which can cause weight gain, tiredness and skin problems.',
        'hyperthyroidism'     => 'an overactive thyroid gland (common in older cats), causing weight loss despite a big appetite.',
        'kidney disease'      => 'the kidneys are not filtering the blood well. Signs are drinking and peeing more.',
        'renal'               => 'related to the kidneys.',
        'hepatic'             => 'related to the liver.',
        'cardiac'             => 'related to the heart.',
        'murmur'              => 'an extra "whooshing" sound in the heartbeat. Some are harmless, others need more tests.',
        'prognosis'           => 'the expected outcome: how likely the pet is to get better.',
        'diagnosis'           => 'the name of the problem the vet found.',
        'chronic'             => 'long-lasting or coming back again and again.',
        'acute'               => 'sudden and recent.',
        'bid'                 => 'twice a day (about every 12 hours).',
        'sid'                 => 'once a day.',
        'tid'                 => 'three times a day (about every 8 hours).',
        'prn'                 => 'only when needed.',
        'po'                  => 'by mouth.',
        'subcutaneous'        => 'given as an injection under the skin.',
        'topical'             => 'put on the skin, not swallowed.',
        'follow-up'           => 'a check-up visit to see how the pet is healing.',
        'e-collar'            => 'the cone-shaped collar that stops the pet from licking or scratching a wound.',
    ];

    /**
     * @return string A plain-language answer, ready to show in the chat.
     */
    public function answer(string $question, array $pet = []): string
    {
        $found = $this->findTerms($question);
        $name  = $pet['name'] ?? 'your pet';

        if ($found === []) {
            return 'I can explain veterinary words in simple language, for example "otitis", "pyoderma", "BID" or "CBC". '
                . 'Type the word or phrase from ' . $name . '\'s record and I will explain it. '
                . 'For questions about treatment or doses, please ask your veterinarian.'
                . self::NOTE;
        }

        $lines = [];
        foreach ($found as $term => $meaning) {
            $label   = in_array($term, self::ABBREVIATIONS, true) ? strtoupper($term) : ucfirst($term);
            $lines[] = '• ' . $label . ': ' . $meaning;
        }

        return "Here is what these words mean:\n" . implode("\n", $lines)
            . "\n\nIf you are worried about " . $name . ', please call the clinic.'
            . self::NOTE;
    }

    /** The glossary terms that appear in the text, longest first, without terms inside longer ones. */
    private function findTerms(string $text): array
    {
        $text  = ' ' . strtolower($text) . ' ';
        $terms = array_keys(self::TERMS);
        usort($terms, static fn ($a, $b) => strlen($b) <=> strlen($a));

        $found = [];
        foreach ($terms as $term) {
            // Whole words only, so "po" does not match inside "poison"
            if (preg_match('/(?<![a-z])' . preg_quote($term, '/') . '(?![a-z])/', $text)) {
                $alreadyCovered = false;
                foreach (array_keys($found) as $longer) {
                    if (str_contains($longer, $term)) {
                        $alreadyCovered = true;
                    }
                }
                if (! $alreadyCovered) {
                    $found[$term] = self::TERMS[$term];
                }
            }
            if (count($found) >= 5) {
                break;
            }
        }

        return $found;
    }
}
