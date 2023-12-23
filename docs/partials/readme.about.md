Helm + Helmfile for Docker compose, implemented as a Symfony command.

## About Shipyard

Shipyard is a tool for orchestrating Docker swarm clusters and stacks.

It is heavily inspired by [Helm](https://helm.sh/) and [helmfile](https://github.com/helmfile/helmfile) and provides the same concepts in a non-kubernetes but swarm-enabled environment. 

## Concepts:

* **Shipyard Chart**: A package defining a docker compose stack and all of it's related files (config files, envs, etc), similar to a Helm Chart, a yum RPM file or a Homebrew formula. A chart contains all resource definitions necessary to deploy and run an application as a Docker Swarm stack, arranged in a specific layout. A chart may be used to deploy a simple application, or a full web application stack with multiple dependencies, such as HTTP servers, database, caches and so on. (similar to a Helm Chart)
* **Shipyard Stack**: an instance of a Shipyard Chart, customized through a `values.yaml` file. (similar to a Helm Release)
* **shipyard.yaml**: a file defining which Charts to instantiate using which values on which docker hosts. (similar to a helmfile.yaml file)

As you can see, the concepts are very similar to Helm and helmfile. The main difference is that Shipyard is not kubernetes-specific and does not require a kubernetes cluster to run. Instead, it uses Docker Swarm to deploy the stacks.

