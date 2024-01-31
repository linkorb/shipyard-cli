<!-- Managed by https://github.com/linkorb/repo-ansible. Manual changes will be overwritten. -->
shipyard-cli
============

Helm + Helmfile for Docker compose, implemented as a Symfony command.

## About Shipyard

Shipyard is a tool for orchestrating Docker swarm clusters and stacks.

It is heavily inspired by [Helm](https://helm.sh/) and [helmfile](https://github.com/helmfile/helmfile) and provides the same concepts in a non-kubernetes but swarm-enabled environment. 

## Concepts:

* **Shipyard Chart**: A package defining a docker compose stack and all of it's related files (config files, envs, etc), similar to a Helm Chart, a yum RPM file or a Homebrew formula. A chart contains all resource definitions necessary to deploy and run an application as a Docker Swarm stack, arranged in a specific layout. A chart may be used to deploy a simple application, or a full web application stack with multiple dependencies, such as HTTP servers, database, caches and so on. (similar to a Helm Chart)
* **Shipyard Stack**: an instance of a Shipyard Chart, customized through a `values.yaml` file. (similar to a Helm Release)
* **shipyard.yaml**: a file defining which Charts to instantiate using which values on which docker hosts. (similar to a helmfile.yaml file)

As you can see, the concepts are very similar to Helm and helmfile. The main difference is that Shipyard is not kubernetes-specific and does not require a kubernetes cluster to run. Instead, it uses Docker Swarm to deploy the stacks.





## Usage

## Creating a shipyard.yaml file:

The `shipyard.yaml` file defines which stacks get deployed to which hosts. It is similar to a helmfile.yaml file.

```yaml
# shipyard.yaml
stacks:
  - name: my-traefik
    chart: traefik
    host: localhost
    values: my-traefik/values.yaml
    tag: lb

  - name: my-whoami
    chart: whoami
    host: swarm-host-a
    values: my-whoami/values.sops.yaml
    tag: apps

  - name: my-mariadb
    chart: mariadb
    host: swarm-host-a
    values: my-mariadb/values.yaml
    tag: db

  - name: my-whoami-b
    chart: whoami
    host: swarm-host-b
    values: my-whoami-b/values.yaml
    tag: apps
settings:
  charts_path: example/charts
  target: remote  # Target connection. Values: remote/local
  stack_path: /opt/shipyard/stacks  # Template directory path on the remote host
  shipyard_tag: apps # Optionally only deploy stacks with this tag. Default: empty
```

## Creating a Shipyard Chart

Directory structure of a Shipyard Chart:

```
my-shipyard-chart/
  Chart.yaml # the chart metadata
  LICENSE # the license for this chart
  README.md # the readme for this chart
  values.yaml # the default values for this chart
  templates/ # the jinja2 templates for this chart
    docker-compose.yml # the docker compose template file for this chart
    example.conf # an example config file template for this chart
    env.example # another example config file template for this chart
```

The shipyard-cli will copy over all files in the `templates/` directory onto the target host, and then render them using the values from the `values.yaml` file.
If the host is `localhost`, the files will be copied onto the localhost.

## values.yaml / values.sops.yaml and chart default values

Every stack (one instance of a chart), takes a values file containing the values for that instance of the chart.
The values are loaded from `{{stack_path}}/{{stack_name}}/values.yaml`. If a `values.sops.yaml` is detected, it is also loaded and decrypted automatically (based on the `.sops.yaml` in the root of your repo).

Every chat provides a default values.yaml too. Any stack-level value that remains undefined will be set to the chart's default value.

The loading (and override precedence) order is:

1. the default values from the chart
2. the values.yaml from the stack
3. the values.sops.yaml from the stack

## Target host directory structure

On the target hosts(Docker Swarm managers), Shipyard-cli will create the following directory structure:

```
/opt/shipyard/stacks/
  my-shipyard-stack/
    docker-compose.yml # the rendered docker compose file
    example.conf # the rendered example config file
    # ... etc
```

## Deploying the stacks to Docker Swarm
After the templates are rendered and written to the host, the Shipyard-cli will run `docker compose up` on the target host to deploy the docker swarm stack.


## Example Shipyard Chart

See the [example/shipyard/chart/whoami](example/charts/whoami) directory for an example Shipyard Chart.

## Contributing

We welcome contributions to make this repository even better. Whether it's fixing a bug, adding a feature, or improving documentation, your help is highly appreciated. To get started, fork this repository then clone your fork.

Be sure to familiarize yourself with LinkORB's [Contribution Guidelines](/CONTRIBUTING.md) for our standards around commits, branches, and pull requests, as well as our [code of conduct](/CODE_OF_CONDUCT.md) before submitting any changes.

If you are unable to implement changes you like yourself, don't hesitate to open a new issue report so that we or others may take care of it.
## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).
By the way, we're hiring!
