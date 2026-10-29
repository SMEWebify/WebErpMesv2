<?php

return [
    'history' => 'History',
    'referent' => 'Reference quote',
    'revision_short' => 'Revision :revision',
    'variant_short' => 'Variant: :label',

    'new_revision' => 'New revision',
    'new_revision_help' => 'Full copy under the next revision letter; this quote becomes “Obsolete” if it is open or sent',
    'new_revision_confirm' => 'Create a new revision? This quote will become “Obsolete” if it is open or sent.',
    'superseded_by' => 'Superseded by',

    'new_variant' => 'Variant',
    'new_variant_help' => 'Full copy linked to the same reference quote, kept open in parallel',
    'variant_label' => 'Variant label',

    'revision_created' => 'New revision of quote :source: :code',
    'variant_created' => 'Variant of quote :source: :code',

    'close_family_prompt' => 'Quote won: other revisions or variants are still open (:codes).',
    'close_family' => 'Close the others',
    'close_family_confirm' => 'Set the other open quotes of this family to “Closed”?',
    'family_closed' => 'Quotes closed: :codes',

    'refused_superseded' => 'A newer revision already exists (:code): revise that one instead.',
    'refused_template' => 'A template has no revisions or variants.',
    'refused_not_won' => 'Only a won quote can close the other quotes of its family.',

    'pdf_revision' => 'Revision :revision',
    'pdf_variant' => 'Variant: :label',
];
