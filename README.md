# HaProxy Stats Visualizer
A php app for visualize the status of multiple HaProxy instances.  
For every haproxy configured, it will show the current status of backend servers with:  
- Status  
- Uptime  
- LastCheck  
- Downtime    

![Alt text](demo.png?raw=true "Demo Image")

## Prerequisites:
For use this app, every haproxy instance has to expose statistics.  
You can do it editing haproxy.cfg with:   
```
listen stats
  bind :9000
  mode http
  stats enable
  stats hide-version
  stats realm Haproxy\ Statistics
  stats uri /lb_stats
  stats auth user:password
  stats refresh 5s
```

## Usage

This app works inside docker, so to use it simple run it with:   

`docker run -d -p 80:80
-e HAPROXY_BASE_URL=https://haproxy.domain.com/
-e HAPROXY_PATHS=lb_stats1,lb_stats2
 leen15/haproxy-stats-visualizer `

And now if you open the new page it will show a section for every HaProxy Instance.   


## Environment Variables  

You have to set these environment variables:  
`HAPROXY_BASE_URL`: base url where you have your haproxy stats pages (it also support basic auth with user:pass@yourdomain.com/)  
`HAPROXY_PATHS`: A list of paths where haproxy stats are exposed (comma separated)   
`REFRESH_INTERVAL`: For autorefresh (Default value 5 seconds)  


## License

This project is released under the terms of the [MIT license](http://en.wikipedia.org/wiki/MIT_License).

