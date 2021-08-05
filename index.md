[drive-download-20210727T095715Z-001.zip](https://github.com/medhomegit/auth0-PHP/files/6938418/drive-download-20210727T095715Z-001.zip)
# frozen_string_literal: true

require 'jekyll'
require 'memory_profiler'

MemoryProfiler.report(allow_files: 'lib/jekyll-seo-tag') do
  Jekyll::PluginManager.require_from_bundler
  Jekyll::Commands::Build.process({
    "source"             => File.expand_path(ARGV[0]),
    "destination"        => File.expand_path("#{ARGV[0]}/_site"),
    "disable_disk_cache" => true,
  })
  puts ''
end.pretty_print(scale_bytes: true, normalize_paths: true)

