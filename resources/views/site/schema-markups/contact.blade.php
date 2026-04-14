<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "{{$meta_title ?? 'Əlaqə'}}",
  "headline": "{{$meta_title ?? 'Əlaqə - Proweb ilə əlaqə saxlayın'}}",
  "description": "{{ $meta_description ?? 'Proweb ilə əməkdaşlıq, layihə müraciəti və ya suallarınız üçün bizimlə əlaqə saxlayın. Komandamız sizə ən qısa zamanda geri dönüş edəcək və detallı məlumat təqdim edəcək.' }}",
  "url": "{{request()->fullUrl()}}",
  "mainEntity": {
    "@type": "Organization",
    "name": "{{$company_name}}",
    "url": "{{url($currentLocale)}}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{$siteSettings->logo_light_url}}"
    },
    "telephone": "{{$contactInfo->mobile ?? ''}}",
    "email": "{{$contactInfo->email ?? ''}}",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Bakı",
      "addressCountry": "AZ"
    },
    "sameAs": [
      @foreach ($socials as $social)
          "{{$social->url}}"{{!$loop->last ? ',' : null}}
      @endforeach
    ]
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{request()->fullUrl()}}"
  }
}
</script>
