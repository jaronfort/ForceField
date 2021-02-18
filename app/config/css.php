<?php
return [
    'mediaQueries' => [
        // Mobile first
        'mobile' => '', // leave blank to avoid being encapsulated inside a media query
                        
        // Apple Watch
        'apple_watch' => '(max-device-width: 42mm) and (min-device-width: 38mm)',
        // Moto 360 Watch
        'moto_360_watch' => '(max-device-width: 218px) and (max-device-height: 281px)',
        // Smart phone (portrait and landscape)
        'smart_phone' => 'only screen and (min-device-width : 320px) and (max-device-width : 480px)',
        // Smart phone (portrait)
        'smart_phone_portrait' => 'only screen and (max-width : 320px)',
        // Smart phone (landscape)
        'smart_phone_landscape' => 'only screen and (min-width : 321px)',
        // Phablet device (portrait and landscape)
        'phablet' => 'only screen and (min-width: 550px)',
        // Table device (portrait and landscape)
        'tablet' => 'only screen and (min-width: 750px)',
        // Desktops and Laptops
        'desktop' => 'only screen and (min-width: 1000px)',
        // HD Desktops
        'desktop_hd' => 'only screen and (min-width: 1200px)',
        // Large screens
        'large_screen' => 'only screen and (min-width : 1824px)',
        // iPhone 4 and 4S (portrait and landscape)
        'iphone_4' => 'only screen and (min-device-width: 320px) and (max-device-width: 480px)and (-webkit-min-device-pixel-ratio: 2)',
        // iPhone 4 and 4S (portrait)
        'iphone_4_portrait' => 'only screen and (min-device-width: 320px) and (max-device-width: 480px)and (-webkit-min-device-pixel-ratio: 2)and (orientation: portrait)',
        // iPhone 4 and 4S (landscape)
        'iphone_4_landscape' => 'only screen and (min-device-width: 320px) and (max-device-width: 480px)and (-webkit-min-device-pixel-ratio: 2)and (orientation: landscape)',
        // iPhone 5 and 5S (portrait and landscape)
        'iphone_5' => 'only screen and (min-device-width: 320px) and (max-device-width: 568px)and (-webkit-min-device-pixel-ratio: 2)',
        // iPhone 5 and 5S (portrait)
        'iphone_5_portrait' => 'only screen and (min-device-width: 320px) and (max-device-width: 568px)and (-webkit-min-device-pixel-ratio: 2)and (orientation: portrait)',
        // iPhone 5 and 5S (landscape)
        'iphone_5_landscape' => 'only screen and (min-device-width: 320px) and (max-device-width: 568px)and (-webkit-min-device-pixel-ratio: 2)and (orientation: landscape)',
        // iPhone 6 (portrait and landscape)
        'iphone_6' => 'only screen and (min-device-width: 375px) and (max-device-width: 667px) and (-webkit-min-device-pixel-ratio: 2)',
        // iPhone 6 (portrait)
        'iphone_6_portrait' => 'only screen and (min-device-width: 375px) and (max-device-width: 667px) and (-webkit-min-device-pixel-ratio: 2)and (orientation: portrait)',
        // iPhone 6 (landscape)
        'iphone_6_landscape' => 'only screen and (min-device-width: 375px) and (max-device-width: 667px) and (-webkit-min-device-pixel-ratio: 2)and (orientation: landscape)',
        // iPhone 6+ (portrait and landscape)
        'iphone_6_plus' => 'only screen and (min-device-width: 414px) and (max-device-width: 736px) and (-webkit-min-device-pixel-ratio: 3)',
        // iPhone 6+ (portrait)
        'iphone_6_plus_portrait' => 'only screen and (min-device-width: 414px) and (max-device-width: 736px) and (-webkit-min-device-pixel-ratio: 3)and (orientation: portrait)',
        // iPhone 6+ (landscape)
        'iphone_6_plus_landscape' => 'only screen and (min-device-width: 414px) and (max-device-width: 736px) and (-webkit-min-device-pixel-ratio: 3)and (orientation: landscape)',
        // Galaxy S3 (portrait and landscape)
        'galaxy_s3' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 2)',
        // Galaxy S3 (portrait)
        'galaxy_s3_portrait' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)',
        // Galaxy S3 (landscape)
        'galaxy_s3_landscape' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)',
        // Galaxy S4 (portrait and landscape)
        'galaxy_s4' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3)',
        // Galaxy S4 (portrait)
        'galaxy_s4_portrait' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
        // Galaxy S4 (landscape)
        'galaxy_s4_lanscape' => 'screen and (device-width: 320px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
        // Galaxy S5 (portrait and landscape)
        'galaxy_s5' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3)',
        // Galaxy S5 (portrait)
        'galaxy_s5_portrait' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
        // Galaxy S5 (landscape)
        'galaxy_s5_landscape' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
        // HTC One (portrait and landscape)
        'htc_one' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) ',
        // HTC One (portrait)
        'htc_one_portrait' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)',
        // HTC One (landscape)
        'htc_one_landscape' => 'screen and (device-width: 360px) and (device-height: 640px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)',
        // iPad Mini (portrait and landscape)
        'ipad_mini' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad Mini (portrait)
        'ipad_mini_portrait' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad Mini (landscape)
        'ipad_mini_landscape' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad 1 & 2 (portrait and landscape)
        'ipad_1_and_2' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad 1 & 2 (portrait)
        'ipad_1_and_2_portrait' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad 1 & 2 (landscape)
        'ipad_1_and_2_landscape' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 1)',
        // iPad 3 & 4 (portrait and landscape)
        'ipad_3_and_4' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (-webkit-min-device-pixel-ratio: 2)',
        // iPad 3 & 4 (portrait)
        'ipad_3_and_4_portrait' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 2)',
        // iPad 3 & 4 (landscape)
        'ipad_3_and_4_landscape' => 'only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 2)',
        // Galaxy Tab 10.1 (portrait and landscape)
        'galaxy_tab_10' => '(min-device-width: 800px) and (max-device-width: 1280px)',
        // Galaxy Tab 10.1 (portrait)
        'galaxy_tab_10_portrait' => '(max-device-width: 800px) and (orientation: portrait)',
        // Galaxy Tab 10.1 (landscape)
        'galaxy_tab_10_landscape' => '(max-device-width: 1280px) and (orientation: landscape)',
        // Asus Nexus 7 (portrait and landscape)
        'asus_nexus_7' => 'screen and (device-width: 601px) and (device-height: 906px) and (-webkit-min-device-pixel-ratio: 1.331) and (-webkit-max-device-pixel-ratio: 1.332)',
        // Asus Nexus 7 (portrait)
        'asus_nexus_7_portrait' => 'screen and (device-width: 601px) and (device-height: 906px) and (-webkit-min-device-pixel-ratio: 1.331) and (-webkit-max-device-pixel-ratio: 1.332) and (orientation: portrait)',
        // Asus Nexus 7 (landscape)
        'asus_nexus_7_landscape' => 'screen and (device-width: 601px) and (device-height: 906px) and (-webkit-min-device-pixel-ratio: 1.331) and (-webkit-max-device-pixel-ratio: 1.332) and (orientation: landscape)',
        // Kindle Fire HD 7" (portrait and landscape)
        'kindle_fire_hd_7' => 'only screen and (min-device-width: 800px) and (max-device-width: 1280px) and (-webkit-min-device-pixel-ratio: 1.5)',
        // Kindle Fire HD 7" (portrait)
        'kindle_fire_hd_7_portrait' => 'only screen and (min-device-width: 800px) and (max-device-width: 1280px) and (-webkit-min-device-pixel-ratio: 1.5) and (orientation: portrait)',
        // Kindle Fire HD 7" (landscape)
        'kindle_fire_hd_7_landscape' => 'only screen and (min-device-width: 800px) and (max-device-width: 1280px) and (-webkit-min-device-pixel-ratio: 1.5) and (orientation: landscape)',
        // Kindle Fire HD 8.9" (portrait and landscape)
        'kindle_fire_hd_8' => 'only screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 1.5)',
        // Kindle Fire HD 8.9" (portrait)
        'kindle_fire_hd_8_portrait' => 'only screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 1.5) and (orientation: portrait)',
        // Kindle Fire HD 8.9" (landscape)
        'kindle_fire_hd_8_landscape' => 'only screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 1.5) and (orientation: landscape)',
        // Laptops with non retina displays
        'non_retina_screens' => 'screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 1)',
        // Laptops with retina displays
        'retina_screens' => 'screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 2) and (min-resolution: 192dpi)'
    
    ],
    'properties' => [
        // Standard properties
        'display' => null,
        'position' => null,
        'visibility' => null,
        'float' => null,
        'color' => null,
        'fill' => null,
        'opacity' => null,
        'clear' => null,
        'left' => null,
        'right' => null,
        'top' => null,
        'bottom' => null,
        'clip' => null,
        'direction' => null,
        'width' => null,
        'minWidth' => 'min-width',
        'height' => null,
        'minHeight' => 'min-height',
        'overflow' => null,
        'overflowX' => 'overflow-x',
        'overflowY' => 'overflow-y',
        'verticalAlign' => 'vertical-align',
        'zIndex' => 'z-index',
        'content' => null,
        'cursor' => null,
        'margin' => null,
        'marginLeft' => 'margin-left',
        'marginRight' => 'margin-right',
        'marginTop' => 'margin-top',
        'marginBottom' => 'margin-bottom',
        'padding' => null,
        'paddingLeft' => 'padding-left',
        'paddingRight' => 'padding-right',
        'paddingTop' => 'padding-top',
        'paddingBottom' => 'padding-bottom',
        'background' => null,
        'backgroundAttachment' => 'background-attachment',
        'backgroundBlendMode' => 'background-blend-mode',
        'backgroundColor' => 'background-color',
        'backgroundImage' => 'background-image',
        'backgroundPosition' => 'background-position',
        'backgroundRepeat' => 'background-repeat',
        'backgroundClip' => 'background-clip',
        'backgroundOrigin' => 'background-origin',
        'backgroundSize' => 'background-size',
        'border' => null,
        'borderBottom' => 'border-bottom',
        'borderBottomColor' => 'border-bottom-color',
        'borderBottomLeftRadius' => 'border-bottom-left-radius',
        'borderBottomRightRadius' => 'border-bottom-right-radius',
        'borderBottomStyle' => 'border-bottom-style',
        'borderBottomWidth' => 'border-bottom-width',
        'borderColor' => 'border-color',
        'borderImage' => 'border-image',
        'borderImageOutset' => 'border-image-outset',
        'borderImageRepeat' => 'border-image-repeat',
        'borderImageSlice' => 'border-image-slice',
        'borderImageSource' => 'border-image-source',
        'borderImageWidth' => 'border-image-width',
        'borderImageRepeat' => 'border-image-repeat',
        'borderLeft' => 'border-left',
        'borderLeftColor' => 'border-left-color',
        'borderLeftStyle' => 'border-left-style',
        'borderLeftWidth' => 'border-left-width',
        'borderRadius' => 'border-radius',
        'borderRight' => 'border-right',
        'borderRightColor' => 'border-right-color',
        'borderRightStyle' => 'border-right-style',
        'borderRightWidth' => 'border-right-width',
        'borderStyle' => 'border-style',
        'borderTop' => 'border-top',
        'borderTopColor' => 'border-top-color',
        'borderTopLeftRadius' => 'border-top-left-radius',
        'borderTopRightRadius' => 'border-top-right-radius',
        'borderTopStyle' => 'border-top-style',
        'borderTopWidth' => 'border-top-width',
        'borderWidth' => 'border-width',
        'boxDecorationBreak' => 'box-decoration-break',
        'boxShadow' => 'box-shadow',
        'alignContent' => 'align-content',
        'alignItems' => 'align-items',
        'alignSelf' => 'align-self',
        'flex' => null,
        'flexBasis' => 'flex-basis',
        'flexDirection' => 'flex-direction',
        'flexFlow' => 'flex-flow',
        'flexGrow' => 'flex-grow',
        'flexShrink' => 'flex-shrink',
        'flexWrap' => 'flex-wrap',
        'justifyContent' => 'justify-content',
        'order' => null,
        'hangingPunctuation' => 'hanging-punctuation',
        'hyphens' => null,
        'letterSpacing' => 'letter-spacing',
        'lineBreak' => 'line-break',
        'lineHeight' => 'line-height',
        'overflowWrap' => 'overflow-wrap',
        'tabSize' => 'tab-size',
        'textAlign' => 'text-align',
        'textAlignLast' => 'text-align-last',
        'textCombineUpright' => 'text-combine-upright',
        'textIndent' => 'text-indent',
        'textJustify' => 'text-justify',
        'textTransform' => 'text-transform',
        'whiteSpace' => 'white-space',
        'wordBreak' => 'word-break',
        'wordSpacing' => 'word-spacing',
        'wordWrap' => 'word-wrap',
        'textDecoration' => 'text-decoration',
        'textDecorationColor' => 'text-decoration-color',
        'textDecorationLine' => 'text-decoration-line',
        'textDecorationStyle' => 'text-decoration-style',
        'textShadow' => 'text-shadow',
        'textUnderlinePosition' => 'text-underline-position',
        'font' => null,
        'fontFamily' => 'font-family',
        'fontFeatureSettings' => 'font-feature-settings',
        'fontKerning' => 'font-kerning',
        'fontLanguageOverride' => 'font-language-override',
        'fontSize' => 'font-size',
        'fontSizeAdjust' => 'font-size-adjust',
        'fontStretch' => 'font-stretch',
        'fontStyle' => 'font-style',
        'fontSynthesis' => 'font-synthesis',
        'fontVariant' => 'font-variant',
        'fontVariantAlternates' => 'font-variant-alternates',
        'fontVariantCaps' => 'font-variant-caps',
        'fontVariantEastAsian' => 'font-variant-east-asian',
        'fontVariantLigatures' => 'font-variant-ligatures',
        'fontVariantNumeric' => 'font-variant-numeric',
        'fontVariantPosition' => 'font-variant-position',
        'fontWeight' => 'font-weight',
        'textOrientation' => 'text-orientation',
        'textCombineUpright' => 'text-combine-upright',
        'unicodeBidi' => 'unicode-bidi',
        'userSelect' => 'user-select',
        'writingMode' => 'writing-mode',
        'borderCollapse' => 'border-collapse',
        'borderSpacing' => 'border-spacing',
        'captionSide' => 'caption-side',
        'emptyCells' => 'empty-cells',
        'tableLayout' => 'table-layout',
        'counterIncrement' => 'counter-increment',
        'counterReset' => 'counter-reset',
        'listStyle' => 'list-style',
        'listStyleImage' => 'list-style-image',
        'listStylePosition' => 'list-style-position',
        'listStyleType' => 'list-style-type',
        'animation' => null,
        'animationDeley' => 'animation-delay',
        'animationDirection' => 'animation-direction',
        'animationDuration' => 'animation-duration',
        'animationFillMode' => 'animation-fill-mode',
        'animationIterationCount' => 'animation-iteration-count',
        'animationName' => 'animation-name',
        'animationPlayState' => 'animation-play-state',
        'animationTimingFunction' => 'animation-timing-function',
        'backfaceVisibility' => 'backface-visibility',
        'perspective' => null,
        'perspectiveOrigin' => 'perspective-origin',
        'transform' => null,
        'transformOrigin' => 'transform-origin',
        'transformStyle' => 'transform-style',
        'transition' => null,
        'transitionProperty' => 'transition-property',
        'transitionDuration' => 'transition-duration',
        'transitionTimingFunction' => 'transition-timing-function',
        'transitionDelay' => 'transition-delay',
        'boxSizing' => 'box-sizing',
        'imeMode' => 'ime-mode',
        'outline' => null,
        'outlineColor' => 'outline-color',
        'outlineOffset' => 'outline-offset',
        'outlineStyle' => 'outline-style',
        'outlineWidth' => 'outline-width',
        'textOverflow' => 'text-overflow',
        'breakAfter' => 'break-after',
        'breakBefore' => 'break-before',
        'breakInside' => 'break-inside',
        'columnCount' => 'column-count',
        'columnFill' => 'column-fill',
        'column' => 'column-gap',
        'columnColor' => 'column-rule',
        'columnRuleColor' => 'column-rule-color',
        'columnRuleStyle' => 'column-rule-style',
        'columnRuleWidth' => 'column-rule-width',
        'columnSpan' => 'column-span',
        'columnWidth' => 'column-width',
        'columns' => null,
        'widows' => null,
        'orphans' => null,
        'pageBreakAfter' => 'page-break-after',
        'pageBreakBefore' => 'page-break-before',
        'pageBreakInside' => 'page-break-inside',
        'marks' => null,
        'mark' => null,
        'markAfter' => 'mark-after',
        'markBefore' => 'mark-before',
        'phonemes' => null,
        'rest' => null,
        'restAfter' => 'rest-after',
        'restBefore' => 'rest-before',
        'voiceBalance' => 'voice-balance',
        'voiceDuration' => 'voice-duration',
        'voicePitch' => 'voice-pitch',
        'voicePitchRange' => 'voice-pitch-range',
        'voiceRate' => 'voice-rate',
        'voiceStress' => 'voice-stress',
        'voiceVolume' => 'voice-volume',
        'quotes' => null,
        'filter' => null,
        'imageOrientation' => 'image-orientation',
        'imageRendering' => 'image-rendering',
        'imageResolution' => 'image-resolution',
        'objectFit' => 'object-fit',
        'objectPosition' => 'object-position',
        'mask' => null,
        'maskType' => 'mask-type',
        'src' => null,
        'unicodeRange' => 'unicode-range',
        
        // Aliases
        'blendMode' => 'background-blend-mode'
    ]
];