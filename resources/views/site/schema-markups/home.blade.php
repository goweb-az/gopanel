<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "{{url($currentLocale)}}#organization",
  "name": "{{$company_name}}",
  "url": "{{url($currentLocale)}}",
  "logo": {
    "@type": "ImageObject",
    "url": "{{$siteSettings->logo_light_url}}",
    "width": 300,
    "height": 60
  },
  "description": "{{ $meta_description ?? 'Veb sayt hazırlanması, SEO optimizasiya, mobil tətbiqlər və proqram təminatı üzrə peşəkar rəqəmsal həllər' }}",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "{{$contactInfo->mobile ?? ''}}",
    "contactType": "customer support",
    "availableLanguage": ["az", "en", "ru"]
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Bakı",
    "addressCountry": "AZ"
  },
  "sameAs": [
    @foreach ($socials as $social)
        "{{$social->url}}"{{!$loop->last ? ',' : null}}
    @endforeach
  ],
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{url($currentLocale)}}"
  }
}
</script>
@isset($schema_faqs)
    @if ($schema_faqs->count())
@include("site.schema-markups.faq", ['schema_faqs' => $schema_faqs])
    @endif
@endisset
